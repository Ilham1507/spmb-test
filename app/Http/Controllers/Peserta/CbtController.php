<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\CbtAccessSession;
use App\Models\JawabanCbtPendaftar;
use App\Models\PertanyaanCbt;
use App\Models\PesertaTes;
use App\Models\TesMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CbtController extends Controller
{
    public function index()
    {
        $pendaftar = Auth::user()?->pendaftar;
        $session = $this->activeSession($pendaftar?->id);
        $lockedSession = !$session && $pendaftar
            ? CbtAccessSession::where('applicant_id', $pendaftar->id)
                ->where('status', 'locked')
                ->latest()
                ->first()
            : null;
        $questions = $session
            ? $this->orderedQuestions($session, $pendaftar->id)
            : collect();

        $answers = $pendaftar
            ? JawabanCbtPendaftar::where('applicant_id', $pendaftar->id)->pluck('answer', 'question_id')
            : collect();

        return view('peserta.cbt.index', compact('pendaftar', 'session', 'lockedSession', 'questions', 'answers'));
    }

    public function lock(Request $request)
    {
        $pendaftar = Auth::user()?->pendaftar;
        $session = $this->activeSession($pendaftar?->id);

        if ($session) {
            $session->update([
                'status' => 'locked',
                'location_note' => 'Sesi dikunci otomatis karena peserta meninggalkan halaman CBT. Panitia dapat membuka ulang akses.',
            ]);
        }

        return response()->json(['locked' => (bool) $session]);
    }

    public function saveAnswer(Request $request)
    {
        $pendaftar = Auth::user()?->pendaftar;
        $session = $this->activeSession($pendaftar?->id);

        if (!$pendaftar || !$session) {
            return response()->json(['message' => 'Sesi CBT tidak aktif.'], 422);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:pertanyaan_cbt,id'],
            'answer' => ['required', 'in:A,B,C,D'],
        ]);

        $question = PertanyaanCbt::whereKey($validated['question_id'])->where('status', true)->first();
        if (!$question) {
            return response()->json(['message' => 'Soal CBT tidak tersedia.'], 422);
        }

        JawabanCbtPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id, 'question_id' => $question->id],
            [
                'answer' => $validated['answer'],
                'is_correct' => $question->correct_answer
                    && strtoupper($validated['answer']) === strtoupper($question->correct_answer),
            ]
        );

        return response()->json(['saved' => true]);
    }

    public function submit(Request $request)
    {
        $pendaftar = Auth::user()?->pendaftar;
        $timedOut = $request->boolean('timed_out');
        $automaticFinish = $timedOut;
        $session = $this->activeSession($pendaftar?->id);

        if (!$session && $timedOut && $pendaftar) {
            $session = CbtAccessSession::where('applicant_id', $pendaftar->id)
                ->where('status', 'open')
                ->whereNull('closed_at')
                ->where('expires_at', '<', now())
                ->where('expires_at', '>=', now()->subMinutes(5))
                ->latest()
                ->first();
        }

        if (!$pendaftar || !$session) {
            return redirect()->route('peserta.cbt')
                ->with('warning', 'Akses CBT belum dibuka atau sesi sudah selesai. Minta panitia membuka akses saat kamu berada di lokasi tes.');
        }

        $questions = $this->orderedQuestions($session, $pendaftar->id);

        $rules = ['answers' => [$automaticFinish ? 'nullable' : 'required', 'array']];
        $messages = ['answers.required' => 'Jawaban CBT belum terisi.'];

        if (!$automaticFinish) {
            foreach ($questions as $question) {
                $rules['answers.' . $question->id] = ['required', 'in:A,B,C,D'];
                $messages['answers.' . $question->id . '.required'] = 'Semua soal CBT wajib dijawab sebelum dikirim.';
            }
        }

        $validated = $request->validate($rules, $messages);
        $submittedAnswers = $validated['answers'] ?? [];

        $correct = 0;

        foreach ($questions as $question) {
            $answer = $submittedAnswers[$question->id] ?? null;
            $isCorrect = $answer && $question->correct_answer && strtoupper($answer) === strtoupper($question->correct_answer);

            if ($isCorrect) {
                $correct++;
            }

            JawabanCbtPendaftar::updateOrCreate(
                [
                    'applicant_id' => $pendaftar->id,
                    'question_id' => $question->id,
                ],
                [
                    'answer' => $answer,
                    'is_correct' => $isCorrect,
                ]
            );
        }

        $score = $questions->count() > 0 ? round(($correct / $questions->count()) * 100, 2) : 0;

        $test = TesMasuk::firstOrCreate(
            ['test_name' => 'Tes CBT'],
            [
                'test_date' => now()->toDateString(),
                'location' => 'Kampus E SMK Muhammadiyah 4 Cileungsi',
                'description' => 'Tes CBT',
            ]
        );

        PesertaTes::updateOrCreate(
            [
                'test_id' => $test->id,
                'applicant_id' => $pendaftar->id,
            ],
            [
                'attendance' => true,
                'score' => $score,
                'notes' => 'Dikerjakan siswa melalui sistem CBT. Benar ' . $correct . ' dari ' . $questions->count() . ' soal.',
            ]
        );

        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()->route('peserta.hasil-tes.index')
            ->with('success', $timedOut
                ? 'Waktu CBT habis. Jawaban yang sudah terisi sudah diterima sistem.'
                : 'Jawaban CBT berhasil dikirim. Hasil Tes SPMB akan diumumkan tiga hari setelah tanggal tes.');
    }

    private function activeSession(?int $applicantId): ?CbtAccessSession
    {
        if (!$applicantId) {
            return null;
        }

        return CbtAccessSession::where('applicant_id', $applicantId)
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();
    }

    private function orderedQuestions(CbtAccessSession $session, int $applicantId)
    {
        return PertanyaanCbt::where('status', true)
            ->get()
            ->sortBy(fn (PertanyaanCbt $question) => hash(
                'sha256',
                $session->id . ':' . $applicantId . ':' . $question->id
            ))
            ->values();
    }
}

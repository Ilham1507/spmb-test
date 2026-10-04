<?php

namespace Tests\Unit;

use Tests\TestCase;

class StudentLoginModalTest extends TestCase
{
    public function test_student_login_dialog_is_teleported_above_admin_chrome_and_links_role_management(): void
    {
        $view = file_get_contents(resource_path('views/admin/siswa/index.blade.php'));
        $this->assertStringContainsString('<template x-teleport="body">', $view);
        $this->assertStringContainsString('z-[2147483000]', $view);
        $this->assertStringContainsString('min-h-0 flex-1 overflow-y-auto', $view);
        $this->assertStringContainsString('grid shrink-0 grid-cols-2', $view);
        $this->assertStringContainsString("route('admin.users.index', ['search' => \$student->user->phone])", $view);
        $this->assertStringContainsString('autocomplete="new-password"', $view);
    }
}

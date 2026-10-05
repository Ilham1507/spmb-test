const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/school-search.js'), 'utf8');

function setup(fetch) {
    const context = { window: {}, AbortController, setTimeout, clearTimeout, fetch };
    vm.runInNewContext(source, context);
    return context.window.createSchoolSearchClient();
}
const response = (value) => ({ ok: true, json: async () => value });

test('starting another search aborts the previous one and ignores its late result', async () => {
    const calls = [];
    const client = setup((url, options) => new Promise(resolve => calls.push({ url, options, resolve })));
    const first = client.search('/search', 'SMP');
    const second = client.search('/search', 'SMP Cileungsi');
    assert.equal(calls[0].options.signal.aborted, true);
    calls[1].resolve(response(['new']));
    assert.deepEqual(await second, ['new']);
    calls[0].resolve(response(['old']));
    assert.equal(await first, null);
});

test('clearing the input cancels an active request without making another', async () => {
    let call;
    const client = setup((url, options) => new Promise(resolve => { call = { options, resolve }; }));
    const pending = client.search('/search', 'SMP');
    assert.equal((await client.search('/search', '  ')).length, 0);
    assert.equal(call.options.signal.aborted, true);
    call.resolve(response(['old']));
    assert.equal(await pending, null);
});

test('choosing a school cancels its outstanding request', async () => {
    let call;
    const client = setup((url, options) => new Promise(resolve => { call = { options, resolve }; }));
    const pending = client.search('/search', 'SMP');
    client.cancel();
    assert.equal(call.options.signal.aborted, true);
    call.resolve(response(['old']));
    assert.equal(await pending, null);
});

test('failed request yields an empty list instead of leaking an exception', async () => {
    const client = setup(async () => { throw new Error('offline'); });
    assert.equal((await client.search('/search', 'SMP')).length, 0);
});

test('every school picker loads the helper and debounces for 500 ms', () => {
    for (const file of [
        'peserta/sekolah/index.blade.php', 'panitia/kunjungan/index.blade.php',
        'panitia/pendaftaran_bantuan/form.blade.php', 'admin/master/sekolah.blade.php',
    ]) {
        const view = fs.readFileSync(path.join(__dirname, '../../resources/views', file), 'utf8');
        assert.match(view, /asset\('js\/school-search\.js'\)/);
        assert.match(view, /@input\.debounce\.500ms/);
        assert.match(view, /@input="cancel(?:School)?Search\(\)"/);
        assert.doesNotMatch(view, /@input\.debounce\.150ms/);
    }
});

test('all four Alpine pickers execute search and selection with the shared client', async () => {
    const school = { id: 1, npsn: '20200001', nama: 'SMP Cileungsi' };
    for (const [file, factory, options, queryField, searchMethod, idField, cancelMethod] of [
        ['peserta/sekolah/index.blade.php', 'schoolPicker', { searchUrl: '/schools' }, 'query', 'search', 'selectedId', 'cancelSearch'],
        ['panitia/kunjungan/index.blade.php', 'visitSchoolPicker', '/schools', 'query', 'search', 'selectedId', 'cancelSearch'],
        ['panitia/pendaftaran_bantuan/form.blade.php', 'assistedForm', { schoolSearchUrl: '/schools' }, 'schoolQuery', 'searchSchool', 'selectedSchoolId', 'cancelSchoolSearch'],
        ['admin/master/sekolah.blade.php', 'schoolFinder', {}, 'query', 'searchSchools', 'selected', 'cancelSearch'],
    ]) {
        const context = { window: {}, AbortController, setTimeout, clearTimeout, fetch: async () => response([school]) };
        vm.runInNewContext(source, context);
        const view = fs.readFileSync(path.join(__dirname, '../../resources/views', file), 'utf8');
        const inline = [...view.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(match => match[1]).join('\n');
        vm.runInNewContext(inline, context);
        const picker = context[factory](options);
        picker.$refs = {}; picker.$nextTick = callback => callback(); picker.isOpen = true;
        picker[queryField] = 'SMP Cileungsi';
        await picker[searchMethod]();
        assert.equal((picker.results || picker.schoolResults)[0].npsn, school.npsn);
        picker.selectSchool(school);
        assert.equal(idField === 'selected' ? picker.selected.npsn : picker[idField], idField === 'selected' ? school.npsn : school.id);
        picker[cancelMethod]();
        assert.equal(idField === 'selected' ? picker.selected.npsn : picker[idField], '');
    }
});

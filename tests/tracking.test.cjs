// Licensed under the GNU GPL v3 or later.
// Run with: node --test tests/tracking.test.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../js/tracking.js'), 'utf8');

function browser(payload, existing = false) {
    const appended = [];
    const window = {};
    const document = {
        currentScript: {getAttribute: () => typeof payload === 'string' ? payload : JSON.stringify(payload)},
        querySelector: () => existing ? {} : null,
        createElement: () => ({}),
        head: {appendChild: tag => appended.push(tag)},
    };
    const context = vm.createContext({window, document});
    return {window, document, appended, run: () => vm.runInContext(source, context)};
}

test('loads asynchronously and queues user, course and activity tags before download', () => {
    const b = browser({projectid: 'w3y4plwlqv', userid: 'admin', tags: {
        MoodleUserID: '30153', Username: 'admin', Email: 'admin@example.invalid', MoodleCourseID: '58081', MoodleCourseModuleID: '420',
    }});
    b.run();
    assert.equal(b.appended.length, 1);
    assert.equal(b.appended[0].async, true);
    assert.equal(b.appended[0].src, 'https://www.clarity.ms/tag/w3y4plwlqv');
    assert.deepEqual(Array.from(b.window.clarity.q, args => Array.from(args)), [
        ['identify', 'admin'], ['set', 'MoodleUserID', '30153'], ['set', 'Username', 'admin'],
        ['set', 'Email', 'admin@example.invalid'],
        ['set', 'MoodleCourseID', '58081'], ['set', 'MoodleCourseModuleID', '420'],
    ]);
});

test('anonymous visitors are tracked without assigning a shared guest identifier', () => {
    const b = browser({projectid: 'abc123', userid: null, tags: {MoodlePageType: 'login-index'}});
    b.run();
    assert.equal(b.appended.length, 1);
    assert.deepEqual(Array.from(b.window.clarity.q, args => Array.from(args)), [['set', 'MoodlePageType', 'login-index']]);
});

test('repeated initialization does not add a second script or reapply identity', () => {
    const b = browser({projectid: 'abc123', userid: '2', tags: {}});
    b.run();
    b.run();
    assert.equal(b.appended.length, 1);
    assert.equal(b.window.clarity.q.length, 1);
});

test('an existing Clarity loader is reused', () => {
    const b = browser({projectid: 'abc123', userid: '2', tags: {}}, true);
    const calls = [];
    b.window.clarity = (...args) => calls.push(args);
    b.run();
    assert.equal(b.appended.length, 0);
    assert.deepEqual(calls, [['identify', '2']]);
});

test('malformed payloads and project IDs do not initialize tracking', () => {
    for (const payload of ['invalid JSON', {projectid: 'bad/../../id', tags: {}}, {projectid: '', tags: {}}]) {
        const b = browser(payload);
        b.run();
        assert.equal(b.appended.length, 0);
        assert.equal(b.window.clarity, undefined);
    }
});

test('user-supplied tag contents remain literal strings', () => {
    const username = '\"</script><script>alert(1)</script> & Ω';
    const b = browser({projectid: 'abc123', userid: '2', tags: {Username: username}});
    b.run();
    assert.equal(b.window.clarity.q[1][2], username);
});

// Licensed under the GNU GPL v3 or later.
// Copyright (c) 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
// SPDX-License-Identifier: GPL-3.0-or-later
// Run with: node --test tests/tracking.test.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../amd/src/tracking.js'), 'utf8')
    .replace('export const init =', 'const init =') + '\nmodule.exports = {init};\n';

function loadModule(window, document) {
    const context = {window, document, module: {exports: {}}, exports: {}};
    context.exports = context.module.exports;
    vm.runInContext(source, vm.createContext(context));
    return context.module.exports;
}

function browser() {
    const appended = [];
    const window = {};
    const document = {
        querySelector: () => null,
        createElement: () => ({}),
        head: {appendChild: tag => appended.push(tag)},
    };
    document.querySelector = (sel) => {
        if (sel.startsWith('script[src^="https://www.clarity.ms/tag/"]') && appended.length) {
            return {};
        }
        return null;
    };
    const mod = loadModule(window, document);
    return {window, document, appended, init: (payload) => mod.init(payload)};
}

test('loads asynchronously and queues user, course and activity tags before download', () => {
    const b = browser();
    b.init({projectid: 'w3y4plwlqv', userid: 'admin', tags: {
        MoodleUserID: '30153', Username: 'admin', Email: 'admin@example.invalid', MoodleCourseID: '58081', MoodleCourseModuleID: '420',
    }});
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
    const b = browser();
    b.init({projectid: 'abc123', userid: null, tags: {MoodlePageType: 'login-index'}});
    assert.equal(b.appended.length, 1);
    assert.deepEqual(Array.from(b.window.clarity.q, args => Array.from(args)), [['set', 'MoodlePageType', 'login-index']]);
});

test('repeated initialization does not add a second script or reapply identity', () => {
    const b = browser();
    b.init({projectid: 'abc123', userid: '2', tags: {}});
    b.init({projectid: 'abc123', userid: '2', tags: {}});
    assert.equal(b.appended.length, 1);
    assert.equal(b.window.clarity.q.length, 1);
});

test('an existing Clarity loader is reused', () => {
    const appended = [];
    const window = {};
    const document = {
        querySelector: () => ({}),
        createElement: () => ({}),
        head: {appendChild: tag => appended.push(tag)},
    };
    const mod = loadModule(window, document);
    const calls = [];
    window.clarity = (...args) => calls.push(args);
    mod.init({projectid: 'abc123', userid: '2', tags: {}});
    assert.equal(appended.length, 0);
    assert.deepEqual(calls, [['identify', '2']]);
});

test('malformed payloads and project IDs do not initialize tracking', () => {
    for (const payload of [null, {projectid: 'bad/../../id', tags: {}}, {projectid: '', tags: {}}]) {
        const b = browser();
        b.init(payload);
        assert.equal(b.appended.length, 0);
        assert.equal(b.window.clarity, undefined);
    }
});

test('user-supplied tag contents remain literal strings', () => {
    const username = '\"</script><script>alert(1)</script> & Ω';
    const b = browser();
    b.init({projectid: 'abc123', userid: '2', tags: {Username: username}});
    assert.equal(b.window.clarity.q[1][2], username);
});

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';

const source = readFileSync(new URL('../../resources/js/Components/Frontend/GlobalOfferProgress.vue', import.meta.url), 'utf8');
const { descriptor } = parse(source);
const compiled = compileScript(descriptor, { id: 'global-offer-progress', inlineTemplate: true }).content;
const executable = compiled.replace(/import\s*\{([^}]+)\}\s*from\s*['"]vue['"];?/g,
    (_, bindings) => `const { ${bindings.replace(/\s+as\s+/g, ': ')} } = Vue;`).replace('export default', 'return');
const Progress = new Function('Vue', executable)(Vue);
const { createSSRApp } = Vue;

const tier = { id: 1, name: 'Global shopping reward', reward_type: 'percentage', reward_value: 10, threshold: 1000, remaining: 505, percent: 49.5, eligible: false, applied: false };
const render = pricing => renderToString(createSSRApp(Progress, { pricing }));

test('enabled tier displays the progress bar below the threshold', async () => {
    const html = await render({ qualifying_subtotal: 495, milestones: [tier], next_milestone: tier, applied_offers: [] });
    assert.match(html, /role="progressbar"/);
    assert.match(html, /aria-valuenow="49.5"/);
    assert.match(html, /width:49.5%/);
    assert.match(html, /৳505/);
    assert.match(html, /10% discount/);
});

test('next tier progress and earned discount appear together', async () => {
    const earned = { ...tier, applied: true, eligible: true, saving: 100 };
    const next = { ...tier, id: 2, threshold: 2000, reward_value: 20, remaining: 1000, percent: 50 };
    const html = await render({ qualifying_subtotal: 1000, milestones: [earned, next], next_milestone: next, applied_offers: [earned] });
    assert.match(html, /aria-valuenow="50"/);
    assert.match(html, /20% discount/);
    assert.match(html, /৳100 saved/);
});

test('completed tiers show a full bar', async () => {
    const earned = { ...tier, applied: true, eligible: true, saving: 100 };
    const html = await render({ qualifying_subtotal: 1000, milestones: [earned], next_milestone: null, applied_offers: [earned] });
    assert.match(html, /aria-valuenow="100"/);
    assert.match(html, /rewards are unlocked/);
});

test('disabled settings and empty carts do not advertise rewards', async () => {
    assert.doesNotMatch(await render({ qualifying_subtotal: 495, milestones: [], applied_offers: [] }), /progressbar/);
    assert.doesNotMatch(await render({ qualifying_subtotal: 0, milestones: [tier], next_milestone: tier, applied_offers: [] }), /progressbar/);
});

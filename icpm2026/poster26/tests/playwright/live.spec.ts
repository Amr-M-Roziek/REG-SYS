import { test } from '@playwright/test';
import fs from 'fs';
import path from 'path';
const BASE = 'https://reg-sys.com/icpm2026/poster26/index.php';
const OUT = path.join(process.cwd(), 'icpm2026/poster26/tests/screenshots');
const FILE_OK = path.join(process.cwd(), 'icpm2026/poster26/files/poster.pdf');
function rand(n: number) { const c = 'abcdefghijklmnopqrstuvwxyz0123456789'; let s = ''; for (let i = 0; i < n; i++) s += c[Math.floor(Math.random()*c.length)]; return s; }
function email() { return `${rand(8)}+${Date.now()}@example.com`; }
function coemail() { return `${rand(8)}+${Date.now()}@mailhost.com`; }
function ensureDir(p: string) { if (!fs.existsSync(p)) fs.mkdirSync(p, { recursive: true }); }
function writeJson(p: string, data: any) { fs.writeFileSync(p, JSON.stringify(data, null, 2)); }
test('live registration suite', async ({ page }) => {
  ensureDir(OUT);
  const cases = [
    { name:'T1-valid-0co', co:0, file:FILE_OK, weak:false, dupMain:false, invalidCo:false, dupCo:false },
    { name:'T2-valid-1co', co:1, file:FILE_OK, weak:false, dupMain:false, invalidCo:false, dupCo:false },
    { name:'T3-valid-5co', co:5, file:FILE_OK, weak:false, dupMain:false, invalidCo:false, dupCo:false },
    { name:'T4-coemail-equals-main', co:1, file:FILE_OK, weak:false, dupMain:true, invalidCo:false, dupCo:false },
    { name:'T5-duplicate-coemails', co:2, file:FILE_OK, weak:false, dupMain:false, invalidCo:false, dupCo:true },
    { name:'T6-invalid-coemail-format', co:1, file:FILE_OK, weak:false, dupMain:false, invalidCo:true, dupCo:false },
    { name:'T7-missing-abstract', co:0, file:'', weak:false, dupMain:false, invalidCo:false, dupCo:false },
    { name:'T8-invalid-file-type', co:0, file:path.join(process.cwd(), 'icpm2026/poster26/index.php'), weak:false, dupMain:false, invalidCo:false, dupCo:false },
    { name:'T9-weak-password', co:0, file:FILE_OK, weak:true, dupMain:false, invalidCo:false, dupCo:false },
    { name:'T10-duplicate-email', co:0, file:FILE_OK, weak:false, dupMain:false, invalidCo:false, dupCo:false }
  ];
  const results: any[] = [];
  let baseEmail = email();
  for (let i = 0; i < cases.length; i++) {
    const tc = cases[i];
    const main = i === 9 ? baseEmail : email();
    const co1 = tc.dupMain ? main : (tc.invalidCo ? 'bademail' : coemail());
    const co2 = tc.dupCo ? co1 : coemail();
    page.on('dialog', async d => { await d.dismiss(); });
    await page.goto(BASE, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="fname"]', 'Browser User');
    await page.selectOption('select[name="nationality"]', { label: 'United States' });
    await page.fill('input[name="email"]', main);
    await page.selectOption('select[name="coauthors_count"]', String(tc.co));
    if (tc.co >= 1) {
      await page.fill('input[name="coauth1name"]', 'Co Author 1');
      await page.selectOption('select[name="coauth1nationality"]', { label: 'United States' });
      await page.fill('input[name="coauth1email"]', co1);
    }
    if (tc.co >= 2) {
      await page.fill('input[name="coauth2name"]', 'Co Author 2');
      await page.selectOption('select[name="coauth2nationality"]', { label: 'United States' });
      await page.fill('input[name="coauth2email"]', co2);
    }
    if (tc.co >= 3) {
      await page.fill('input[name="coauth3name"]', 'Co Author 3');
      await page.selectOption('select[name="coauth3nationality"]', { label: 'United States' });
      await page.fill('input[name="coauth3email"]', coemail());
    }
    if (tc.co >= 4) {
      await page.fill('input[name="coauth4name"]', 'Co Author 4');
      await page.selectOption('select[name="coauth4nationality"]', { label: 'United States' });
      await page.fill('input[name="coauth4email"]', coemail());
    }
    if (tc.co >= 5) {
      await page.fill('input[name="coauth5name"]', 'Co Author 5');
      await page.selectOption('select[name="coauth5nationality"]', { label: 'United States' });
      await page.fill('input[name="coauth5email"]', coemail());
    }
    await page.selectOption('select[name="profession"]', { label: 'Physician' });
    await page.selectOption('select[name="category"]', { label: 'Poster Competetion' });
    await page.fill('input[name="organization"]', 'BrowserOrg');
    await page.fill('input[name="password"]', tc.weak ? '12345' : 'BrowserPass123!');
    await page.fill('input[name="contact"]', '1234567890');
    if (tc.file) {
      const fileInput = await page.$('input[name="abstract_file"], #abstractFile');
      if (fileInput) await fileInput.setInputFiles(tc.file);
    }
    await page.click('input[type="submit"][name="signup"]');
    let ok = false;
    for (let j = 0; j < 40; j++) {
      if (page.url().includes('welcome.php')) { ok = true; break; }
      await page.waitForTimeout(250);
    }
    const shotPath = path.join(OUT, `${tc.name}-browser.png`);
    await page.screenshot({ path: shotPath, fullPage: true });
    results.push({ name: tc.name, email: main, ok, url: page.url() });
  }
  writeJson(path.join(OUT, 'results.live.playwright.json'), results);
});

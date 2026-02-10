import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const BASE = 'https://reg-sys.com/icpm2026/poster26/index.php';
const FILE_OK = path.join(process.cwd(), 'icpm2026/poster26/files/poster.pdf');
const LOG = path.join(process.cwd(), 'icpm2026/poster26/submissions.log');

test('Abstract email attempt logged and sent to committee', async ({ page }) => {
  const stamp = Date.now();
  const email = `tester+${stamp}@example.com`;
  await page.goto(BASE);
  await page.getByRole('tab', { name: 'Register' }).click();
  await page.getByLabel('Main Auther Full Name').fill('Playwright Tester');
  await page.getByLabel('Main Auther Nationality').selectOption('Egypt');
  await page.getByLabel('Profession').selectOption('Physician');
  await page.getByLabel('Category').selectOption('Poster Competetion');
  await page.getByLabel('Poster Title').fill('Automation Test Poster');
  await page.getByLabel('Organization - University').fill('Test Org');
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill('pw123456!');
  await page.getByLabel('Mobile/Phone No.').fill('123456789');
  await page.setInputFiles('input[type="file"]', FILE_OK);
  await page.getByRole('button', { name: 'Submit' }).click();
  await page.waitForTimeout(5000);
  const content = fs.readFileSync(LOG, 'utf8');
  expect(content).toMatch(/EMAIL_ATTEMPT.*abstract@icpm\.ae/);
  expect(content).toMatch(/EMAIL_SENT.*abstract@icpm\.ae/);
});

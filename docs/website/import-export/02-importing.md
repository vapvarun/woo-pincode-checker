---
title: Importing areas
description: Import areas from CSV and choose how existing areas are handled.
---

# Importing areas

## Import a file

1. Go to **WB Plugins > Pincode Checker > Import / Export**.
2. Under **Import areas from CSV**, choose your **CSV file (max 10 MB)**.
3. Choose what happens to **Areas that already exist** (see below).
4. Choose **Country for rows without one**.
5. Click **Upload and preview**. You see the first 20 rows with a check result for each.
6. Click **Start import**.

Large files import in the background. You can leave the page. The result appears on the tab and in **Recent imports**, which keeps the last 10 imports. Only one import runs at a time. You can click **Cancel import**. Areas already imported stay.

## Import modes

| Mode | What it does |
|------|--------------|
| Keep them as they are (add new areas only) | Default. Existing areas are skipped. |
| Update them with the values in the file (only the columns your file has) | Existing areas change only in the columns your file contains. Other values stay. If a code appears twice, the last row wins. |
| Delete all current areas first, then import the file | Removes every area, then imports. Type REPLACE to confirm. |

Update mode is safe for partial files. A file with only `code` and `days_min` changes only the delivery minimum.

## Failed rows

After the import, click **Download rows that failed**. The report lists the line number, code and reason. Fix those rows and import them again.

## Duplicates

A row for an area that already exists is counted as skipped in **skip** mode, and as updated in **update** mode.

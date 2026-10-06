#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');

const pdfPath = process.argv[2];
const outputPath = process.argv[3];
const pdfjsRoot = process.argv[4] || path.join(process.env.TEMP || process.env.TMP, 'tan-duoc-pdf-tools', 'node_modules', 'pdfjs-dist');
if (!pdfPath || !outputPath) {
    console.error('Usage: node scripts/extract_danhmuc_thuoc_pdf.js <source.pdf> <output.ndjson> [pdfjs-dist-dir]');
    process.exit(1);
}

const pdfjs = require(path.join(pdfjsRoot, 'legacy', 'build', 'pdf.js'));
const COLUMNS = [
    ['stt_nguon', 45, 60], ['ma_hoat_chat', 60, 86], ['ten_hoat_chat', 86, 124],
    ['duong_dung_dang_bao_che', 124, 150], ['nong_do_ham_luong', 150, 175],
    ['ten_thuoc', 175, 220], ['sdk_gpnk', 220, 260], ['sdk_chuan_hoa', 260, 295],
    ['nha_san_xuat', 295, 329], ['nuoc_san_xuat', 329, 355], ['quy_cach_dong_goi', 355, 381],
    ['don_vi_tinh', 381, 398], ['so_luong', 398, 427], ['don_gia', 427, 455],
    ['thanh_tien', 455, 493], ['nha_thau_trung_thau', 493, 533],
    ['nhom_tieu_chi', 533, 559], ['goi_thau', 559, 583], ['don_vi_cong_bo', 583, 605],
    ['tinh_thanh', 605, 643], ['so_quyet_dinh', 643, 684], ['ngay_cong_bo', 684, 730]
];

function normalize(value) {
    return value.replace(/\s+/g, ' ').replace(/\s+([,.;:)])/g, '$1').replace(/([(])\s+/g, '$1').trim();
}

function columnForX(x) {
    for (let i = 0; i < COLUMNS.length; i++) {
        if (x >= COLUMNS[i][1] && x < COLUMNS[i][2]) return i;
    }
    return -1;
}

function assembleRow(items) {
    const values = COLUMNS.map(() => []);
    items.sort((a, b) => b.y - a.y || a.x - b.x);
    for (const item of items) {
        const index = columnForX(item.x);
        if (index >= 0 && item.text.trim()) values[index].push(item.text.trim());
    }
    const row = {};
    COLUMNS.forEach((column, index) => { row[column[0]] = normalize(values[index].join(' ')); });
    const sttMatch = row.stt_nguon.match(/\d+/);
    row.stt_nguon = sttMatch ? Number(sttMatch[0]) : null;
    return row;
}

(async () => {
    const data = new Uint8Array(fs.readFileSync(pdfPath));
    const document = await pdfjs.getDocument({data, useSystemFonts: true}).promise;
    const output = fs.createWriteStream(outputPath, {encoding: 'utf8'});
    let written = 0;

    for (let pageNumber = 1; pageNumber <= document.numPages; pageNumber++) {
        const page = await document.getPage(pageNumber);
        const content = await page.getTextContent();
        const items = content.items.map(item => ({
            text: item.str,
            x: item.transform[4],
            y: item.transform[5],
            width: item.width,
            height: item.height
        })).filter(item => item.text.trim());

        if (process.env.TAN_DUOC_DEBUG === '1' && pageNumber === 1) {
            console.error('page', page.view, 'items', items.filter(item => item.y >= 450 && item.y <= 480));
            process.exit(0);
        }

        // The activity-code column is stable throughout the file. The source STT
        // column becomes "###" after 999 because the originating spreadsheet cell
        // is too narrow, so it cannot be used as the row anchor for later pages.
        const anchors = items.filter(item => item.x >= 60 && item.x < 86 && /^\d+(?:\.\d+)+$/.test(item.text.trim()));
        anchors.sort((a, b) => b.y - a.y);
        for (let i = 0; i < anchors.length; i++) {
            const current = anchors[i];
            const upper = i === 0 ? current.y + Math.max(18, current.height * 2.5) : current.y + (anchors[i - 1].y - current.y) * 0.6;
            const lower = i === anchors.length - 1 ? current.y - Math.max(24, current.height * 3.5) : anchors[i + 1].y + (current.y - anchors[i + 1].y) * 0.6;
            const rowItems = items.filter(item => item.y <= upper && item.y > lower && item.y < 790);
            const row = assembleRow(rowItems);
            if (!row.ma_hoat_chat || !row.ten_hoat_chat) continue;
            written++;
            if (!row.stt_nguon) row.stt_nguon = written;
            row.source_page = pageNumber;
            output.write(JSON.stringify(row) + '\n');
        }
        if (pageNumber % 25 === 0 || pageNumber === document.numPages) {
            console.error(`Processed ${pageNumber}/${document.numPages} pages; ${written} rows`);
        }
    }
    output.end();
})();

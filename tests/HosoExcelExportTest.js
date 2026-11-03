// Run from the project root: node tests/HosoExcelExportTest.js
"use strict";

var fs = require("fs");
var vm = require("vm");

function assert(condition, message) { if (!condition) throw new Error(message); }
var window = {};
var context = { window: window, console: console };
vm.createContext(context);
vm.runInContext(fs.readFileSync('js/hoso-export.js', 'utf8'), context);
var hosoExcelExport = window.hosoExcelExport;

var day = hosoExcelExport.buildDaySheets([
    { ma_lk: '00001', ma_bn: '0002', ngay_sinh: '20000101', gioi_tinh: '1',
      total_amount: 12345, error_count: 2, update_date: '2026-11-02' }
], [
    { ma_lk: '00001', value: '=HYPERLINK("x")', error: 'Bad <value>&', xml: 'XML1',
      field: 'MA_THE', update_date: '2026-11-02' }
]);
assert(day.length === 2 && day[0].rows.length === 1 && day[1].rows.length === 1,
    'Daily report must have file and error sheets.');
assert(day[0].rows[0][1] === '00001' && day[0].rows[0][2] === '0002',
    'Codes must keep leading zeros.');
assert(day[1].rows[0][6] === '=HYPERLINK("x")', 'Original value must be preserved.');

var monthly = hosoExcelExport.buildMonthSheets('2024-02', [
    { xml: 'XML1', field: 'MA_THE', error: 'Bad value', total: 3, file_count: 2,
      days: { '2024-02-01': 2, '2024-02-29': 1 } }
], [
    { update_date: '2024-02-01', error_count: 2, total_amount: 100000 },
    { update_date: '2024-02-29', error_count: 1, total_amount: 250000 },
    { update_date: '2024-02-29', error_count: 0, total_amount: 999999 }
]);
assert(monthly.length === 1 && monthly[0].header.length === 31,
    'February 2024 must have exactly 29 daily columns.');
assert(monthly[0].rows[0][1] === 2 && monthly[0].rows[0][29] === 1 && monthly[0].rows[0][30] === 3,
    'Daily error counts incorrect.');
assert(monthly[0].rows[2][1] === 100000 && monthly[0].rows[2][29] === 250000 &&
    monthly[0].rows[2][30] === 350000, 'Daily estimated costs incorrect.');

var paths = {}, filename = '';
window.JSZip = function () {
    this.file = function (path, content) { paths[path] = content; };
    this.generate = function () { return 'zip-data'; };
};
window.jQuery = { fn: { dataTable: {
    fileSave: function (blob, name) { filename = name; }
} }, Deferred: function () {
    return { resolve: function () {}, reject: function () {}, promise: function () {} };
} };

hosoExcelExport.download(day, 'day.xlsx');
assert(filename === 'day.xlsx', 'Excel file not saved.');
assert(paths['xl/worksheets/sheet1.xml'] && paths['xl/worksheets/sheet2.xml'],
    'Missing worksheets.');
assert(paths['xl/workbook.xml'].indexOf('T') !== -1, 'Missing workbook.');
assert(paths['xl/styles.xml'].indexOf('<cellStyles count="1">') !== -1 &&
    paths['xl/styles.xml'].indexOf('xfId="0"') !== -1,
    'Workbook styles must include the Normal style required by Excel.');
assert(paths['xl/worksheets/sheet1.xml'].indexOf('defaultRowHeight="15"') !== -1,
    'Worksheet must include Excel required default row height.');
var errorsXml = paths['xl/worksheets/sheet2.xml'];
assert(errorsXml.indexOf('t="inlineStr"') !== -1 && errorsXml.indexOf('<f>') === -1,
    'User-provided cell values must be Excel text, not formulas.');
assert(errorsXml.indexOf('=HYPERLINK(&quot;x&quot;)') !== -1 &&
    errorsXml.indexOf('Bad &lt;value&gt;&amp;') !== -1, 'XML escaping incorrect.');

paths = {};
hosoExcelExport.download(monthly, 'month.xlsx');
assert(paths['xl/worksheets/sheet1.xml'].indexOf('s="2"><v>100000</v>') !== -1,
    'Estimated cost cells must use the accounting style.');
assert(paths['xl/styles.xml'].indexOf('numFmtId="164"') !== -1 &&
    paths['xl/styles.xml'].indexOf('#,##0') !== -1,
    'Accounting style must use zero decimal places.');

console.log('PASS HosoExcelExportTest (2 sheets day, matrix month, safe workbook XML)');

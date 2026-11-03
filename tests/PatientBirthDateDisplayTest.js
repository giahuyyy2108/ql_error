// Run from the project root: node tests/PatientBirthDateDisplayTest.js
"use strict";

var fs = require("fs");
var vm = require("vm");

function readFile(path) {
    return fs.readFileSync(path, "utf8");
}

function extractFunction(content, name, nextMarker) {
    var start = content.indexOf("function " + name + "(");
    var end = content.indexOf(nextMarker, start);
    if (start < 0 || end < 0) throw new Error("Cannot extract " + name);
    return content.substring(start, end);
}

var fileJs = readFile("js/file.js");
var hosoJs = readFile("js/hoso.js");
var context = {};
vm.createContext(context);
vm.runInContext(extractFunction(fileJs, "formatPatientBirthDate", "$('#patient-info-name')"), context);
vm.runInContext(extractFunction(hosoJs, "patientText", "function patientBirth"), context);
vm.runInContext(extractFunction(hosoJs, "patientBirth", "function patientGender"), context);

var cases = [
    ["196402090000", "09/02/1964"],
    ["19640209000000", "09/02/1964"],
    ["19640209", "09/02/1964"],
    ["1964-02-09", "09/02/1964"],
    ["1964-02-09 00:00:00", "09/02/1964"],
    ["1964/02/09T00:00:00", "09/02/1964"],
    ["09/02/1964", "09/02/1964"],
    ["9-2-1964", "09/02/1964"],
    ["1964", "1964"],
    ["", "-"],
    [null, "-"],
    ["unknown", "unknown"]
];

var failures = 0;
var formatters = [
    ["file.js", context.formatPatientBirthDate],
    ["hoso.js", context.patientBirth]
];
for (var f = 0; f < formatters.length; f++) {
    for (var i = 0; i < cases.length; i++) {
        var actual = formatters[f][1](cases[i][0]);
        if (actual !== cases[i][1]) {
            console.error("FAIL " + formatters[f][0] + ": " + cases[i][0] + " => " + actual);
            failures++;
        }
    }
}
console.log(failures ? "FAILED: " + failures : "PASS: " + (formatters.length * cases.length) + " birth-date cases");
process.exitCode = failures ? 1 : 0;

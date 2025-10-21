import test from "node:test";
import assert from "node:assert/strict";

import { FormValidator } from "../js/utils/validator.js";

test("FormValidator.isEmail validates e-mail format", () => {
    assert.equal(FormValidator.isEmail("user@example.com"), true);
    assert.equal(FormValidator.isEmail("invalid-email"), false);
    assert.equal(FormValidator.isEmail("user@localhost"), false);
});

test("FormValidator.isRequired handles strings and nullish values", () => {
    assert.equal(FormValidator.isRequired("data"), true);
    assert.equal(FormValidator.isRequired("   trimmed   "), true);
    assert.equal(FormValidator.isRequired(""), false);
    assert.equal(FormValidator.isRequired(null), false);
    assert.equal(FormValidator.isRequired(undefined), false);
});

test("FormValidator length helpers respect boundaries", () => {
    assert.equal(FormValidator.minLength("abc", 2), true);
    assert.equal(FormValidator.minLength("a", 2), false);
    assert.equal(FormValidator.maxLength("abc", 5), true);
    assert.equal(FormValidator.maxLength("abcdef", 5), false);
});

test("FormValidator number and url validators", () => {
    assert.equal(FormValidator.isNumber("123"), true);
    assert.equal(FormValidator.isNumber("not-a-number"), false);
    assert.equal(FormValidator.isUrl("https://alleynote.com"), true);
    assert.equal(FormValidator.isUrl("notaurl"), false);
});

test("FormValidator password helpers", () => {
    assert.equal(FormValidator.isStrongPassword("Abcdef12"), true);
    assert.equal(FormValidator.isStrongPassword("short1A"), false);
});

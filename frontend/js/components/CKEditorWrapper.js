import {
  destroyRichTextEditor,
  getRichTextEditorContent,
  initRichTextEditor,
} from "./RichTextEditor.js";

export class CKEditorWrapper {
  constructor() {
    this.editor = null;
    this.elementId = null;
  }

  async init(elementId, initialValue = "") {
    this.elementId = elementId;
    this.editor = await initRichTextEditor(elementId, { initialValue });
    return this.editor;
  }

  getData() {
    if (!this.elementId) {
      return "";
    }
    return getRichTextEditorContent(this.elementId) || "";
  }

  async destroy() {
    if (!this.elementId) {
      return;
    }
    await destroyRichTextEditor(this.elementId);
    this.editor = null;
    this.elementId = null;
  }
}

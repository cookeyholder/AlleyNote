/**
 * Inline 錯誤訊息工具
 */

function resolveTarget(target, container = document) {
  if (target instanceof HTMLElement) {
    return target;
  }

  if (typeof target === "string") {
    return container.querySelector(target);
  }

  return null;
}

class InlineErrorManager {
  show(target, message, options = {}) {
    const { container = document, visibleClass = "hidden" } = options;
    const element = resolveTarget(target, container);

    if (!element) {
      return null;
    }

    element.textContent = message;
    element.classList.remove(visibleClass);
    element.dataset.inlineErrorVisible = "true";
    return element;
  }

  clear(target, options = {}) {
    const { container = document, hiddenClass = "hidden" } = options;
    const element = resolveTarget(target, container);

    if (!element) {
      return;
    }

    element.textContent = "";
    element.classList.add(hiddenClass);
    delete element.dataset.inlineErrorVisible;
  }

  clearAll(container = document, selector = "[data-error-for]") {
    const elements = container.querySelectorAll(selector);
    elements.forEach((element) => this.clear(element));
  }
}

export const inlineErrors = new InlineErrorManager();

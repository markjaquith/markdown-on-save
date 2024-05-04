/*
 * decaffeinate suggestions:
 * DS102: Remove unnecessary code created because of implicit returns
 * Full docs: https://github.com/decaffeinate/decaffeinate/blob/main/docs/suggestions.md
 */
(function ($) {
  const app = {
    on() {
      $("body").addClass("cws-markdown");
      this.html.click();
      this.checkbox.attr("checked", true);
      this.buttonOn.show();
      [this.buttonOff, this.html, this.visual, this.htmlButtons].map((a) =>
        a.hide(),
      );
    },
    off() {
      $("body").removeClass("cws-markdown");
      this.checkbox.attr("checked", false);
      this.buttonOn.hide();
      [this.buttonOff, this.html, this.visual, this.htmlButtons].map((a) =>
        a.show(),
      );
    },
    delay(ms, f) {
      return setTimeout(f, ms);
    },
    start() {
      const context = $("#cws-markdown");
      context
        .detach()
        .insertBefore("#submitdiv h2 span, #submitdiv h3 span")
        .show();
      this.buttonOn = $("img.markdown-on", context);
      this.buttonOff = $("img.markdown-off", context);
      this.checkbox = $("#cws_using_markdown");
      this.html = $("#content-html");
      this.visual = $("#content-tmce");
      this.htmlButtonsString = [
        "strong",
        "em",
        "link",
        "block",
        "del",
        "ins",
        "img",
        "ul",
        "ol",
        "li",
        "code",
        "close",
      ]
        .map((a) => "#qt_content_" + a)
        .join(", ");
      this.htmlButtons = $(this.htmlButtonsString);
      this.events();
      this.setFromCheckbox();
    },
    setFromCheckbox() {
      app.checkbox.is(":checked") ? app.on() : app.off();
    },
    events() {
      $([this.buttonOn, this.buttonOff]).each(function () {
        $(this).click(function (e) {
          e.stopPropagation(); // Keep metabox from toggling
          app.checkbox.click();
        });
      });
      this.checkbox.change(this.setFromCheckbox);
    },
  };
  window.markdownOnSaveApp = app;
  // Kind of hacky, but this allows for Quicktags to be added to the DOM first
  $(() => app.delay(0, () => app.start()));
})(window.jQuery);

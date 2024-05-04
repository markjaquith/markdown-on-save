;(function ($) {
	const app = {
		on() {
			$('body').addClass('cws-markdown')
			this.html.click()
			this.checkbox.attr('checked', true)
			this.buttonOn.show()
			;[this.buttonOff, this.html, this.visual, this.htmlButtons].map((a) => a.hide())
		},
		off() {
			$('body').removeClass('cws-markdown')
			this.checkbox.attr('checked', false)
			this.buttonOn.hide()
			;[this.buttonOff, this.html, this.visual, this.htmlButtons].map((a) => a.show())
		},
		delay(ms, f) {
			return setTimeout(f, ms)
		},
		start() {
			const context = $('#cws-markdown')
			context.detach().insertBefore('#submitdiv h2, #submitdiv h3').show()
			this.buttonOn = context.find('img.markdown-on')
			this.buttonOff = context.find('img.markdown-off')
			this.checkbox = $('#cws_using_markdown')
			this.html = $('#content-html')
			this.visual = $('#content-tmce')
			this.htmlButtonsString = [
				'strong',
				'em',
				'link',
				'block',
				'del',
				'ins',
				'img',
				'ul',
				'ol',
				'li',
				'code',
				'close',
			]
				.map((a) => '#qt_content_' + a)
				.join(', ')
			this.htmlButtons = $(this.htmlButtonsString)
			this.events()
			this.setFromCheckbox()
		},
		setFromCheckbox() {
			this.checkbox.is(':checked') ? this.on() : this.off()
		},
		events() {
			;[this.buttonOn, this.buttonOff].forEach((b) => {
				b.on('click', (e) => {
					e.stopPropagation() // Keep metabox from toggling
					this.checkbox.click()
				})
			})
			this.checkbox.on('change', this.setFromCheckbox.bind(this))
		},
	}
	window.markdownOnSaveApp = app
	// Kind of hacky, but this allows for Quicktags to be added to the DOM first
	$(() => app.delay(0, () => app.start()))
})(window.jQuery)

do ($ = jQuery, window) ->
	app = window.markdownOnSaveApp =
		on: ->
			@checkbox.attr 'checked','checked'
			@buttonOn.show()
			@buttonOff.hide()
		off: ->
			@checkbox.removeAttr 'checked'
			@buttonOn.hide()
			@buttonOff.show()
		start: ->
			context    = $ '#cws-markdown'
			context.detach().insertBefore('#submitdiv h3 span').show()
			@buttonOn  = $ 'img.markdown-on',  context
			@buttonOff = $ 'img.markdown-off', context
			@checkbox  = $ '#cws_using_markdown'
			@events()
		events: ->
			@buttonOn.click (e) ->
				e.stopPropagation() # Keep metabox from toggling
				app.off()
			@buttonOff.click (e) ->
				e.stopPropagation() # Keep metabox from toggling
				app.on()
			@checkbox.change ->
				if $(@).attr 'checked' then app.on() else app.off()
	$ -> app.start()

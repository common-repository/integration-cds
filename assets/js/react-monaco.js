/*
 * Copyright 2019 AlexaCRM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */
'use strict';
(function(window, wp, react, monaco) {
  const STORAGE_KEY = 'ngdMonacoEditorOptions'
  const KEY_TAB = 2
  const EDITOR_MIN_LINES = 10

  let el = wp.element.createElement
  let localStorage = window.localStorage
  let recentKey = null

  let textContains = function(text, search) {
    return text.indexOf(search) !== -1
  }

  let isFunction = function(value) {
    return typeof value === "function"
  }

  window.icdsMonacoEditor = class extends react.Component {
    constructor(props) {
      super(props)
      this.editor = null
      this.editorContainer = null

      this.state = {
        editorOptions: {
          wordWrap: false,
          renderWhitespace: 'none',
        },
        instanceCount: icdsMonacoEditor.instanceCount++,
      }
    }

    componentDidMount() {
      let self = this
      let editorContainer = window.document.querySelector('.' + this.props.className + this.state.instanceCount)

      if(editorContainer === null) {
        return
      }

      self.editorContainer = editorContainer

      self.editor = monaco.editor.create(self.editorContainer, {
        language: 'twigLanguage',
        theme: 'twigTheme',
        automaticLayout: true,
        wrappingIndent: 'indent',
        lineNumbers: false,
        lineDecorationsWidth: '3',
        minimap: {
          enabled: false
        },
        folding: false,
        roundedSelection: false,
        scrollBeyondLastLine: false,
        scrollbar: {
          vertical: "hidden",
          handleMouseWheel: false,
          verticalScrollbarSize: 0,
        },
        value: this.props.content,
      })

      if(localStorage && localStorage.getItem(STORAGE_KEY)) {
        try {
          let savedOptions = JSON.parse(localStorage.getItem(STORAGE_KEY))

          self.setState({
            editorOptions: savedOptions,
          })

          self.updateEditorOptions(savedOptions)
        } catch(e) {
          localStorage.removeItem(STORAGE_KEY)
        }
      }

      self.editor.getModel().onDidChangeContent(function(e) {
        if(e.changes[0].text === '' || textContains(e.changes[0].text, e.eol)) {
          self.updateEditorHeight()
        }

        if(isFunction(self.props.onChange)) {
          self.props.onChange(self.editor.getValue())
        }
      })

      self.editor.onKeyUp(function(e) {
        if(isFunction(self.props.onKeyUp)) {
          self.props.onKeyUp(e, self.editor)
        }
      })

      self.editor.onKeyDown(function(e) {
        recentKey = e.keyCode

        if(isFunction(self.props.onKeyDown)) {
          self.props.onKeyDown(e, self.editor)
        }
      })

      self.editor.onDidBlurEditorText(function() {
        if(recentKey === KEY_TAB) {
          self.editor.focus()
        }
      })

      self.updateEditorHeight()
    }

    componentWillUnmount() {
      if(this.editor) {
        this.editor.getModel().dispose()
        this.editor.dispose()
      }
    }

    updateEditorOptions(options) {
      this.editor.updateOptions(options)

      if(localStorage) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(options))
      }
    }

    updateEditorHeight() {
      let height = this.editor.getContentHeight()

      if (height < EDITOR_MIN_LINES * 19){
        height = EDITOR_MIN_LINES * 19
      }

      this.editorContainer.style.height = height + 'px'
    }

    render() {
      let self = this

      return el('div', {
          className: self.props.className + 'Wrapper' + ' wp-block-code',
        },
        el(wp.blockEditor.BlockControls, null,
          el(wp.components.ToolbarGroup, null,
            el(wp.components.ToolbarButton, {
              label: 'Word wrap',
              icon: 'editor-break',
              isPressed: self.state.editorOptions.wordWrap,
              onClick() {
                let stateOptions = self.state.editorOptions

                stateOptions.wordWrap = !stateOptions.wordWrap

                self.setState({
                  editorOptions: stateOptions,
                })
                self.updateEditorOptions(stateOptions)
                self.updateEditorHeight()
              },
            }),
            el(wp.components.ToolbarButton, {
              label: 'Show whitespace characters',
              icon: 'editor-paragraph',
              isPressed: self.state.editorOptions.renderWhitespace === 'all',
              onClick() {
                let stateOptions = self.state.editorOptions

                stateOptions.renderWhitespace = stateOptions.renderWhitespace === 'none' ? 'all' : 'none'

                self.setState({
                  editorOptions: stateOptions,
                })
                self.updateEditorOptions(stateOptions)
                self.updateEditorHeight()
              },
            }),
          )
        ),
        el('div', {
          className: self.props.className + this.state.instanceCount,
          style: {
            height: self.props.height,
          },
        })
      )
    }
  }

  window.icdsMonacoEditor.defaultProps = {
    className: 'icdsEditorContainer',
    height: '20em',
    content: '',
  }

  window.icdsMonacoEditor.instanceCount = 0

}(window, window.wp, window.React, window.monaco))

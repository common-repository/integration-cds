/*
 * Copyright 2022 AlexaCRM
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
(function(window, wp, react, _) {
  // Block has the same name as Monaco block to maintain replaceability in case Monaco block is disabled.
  const BLOCK_NAME = 'icds/gutenberg-monaco-block'

  let el = wp.element.createElement

  const editor = class extends react.Component {
    constructor(props) {
      super(props)

      this.handleOnChange = this.handleOnChange.bind(this)

    }

    handleOnChange(e) {
      this.props.onChange(e.target.value)
    }

    render() {
      let self = this

      return el('textarea', {
        value: self.props.content,
        onChange: self.handleOnChange,
        style: {
          width: '100%',
          height: '350px',
          fontFamily: 'monospace'
        }
      })
    }
  }

  wp.blocks.registerBlockType(BLOCK_NAME, {
    title: 'Twig Plain',
    icon: window.icdsSvgIcon,
    category: 'common',
    attributes: {
      content: {
        type: 'string',
        default: '',
      }
    },
    transforms: {
      from: [{
        type: 'block',
        blocks: ['core/html'],
        transform: function(attributes) {
          let content = _.trim(attributes.content)

          if(_.startsWith(content, '[icds_twig]') && _.endsWith(content, '[/icds_twig]')) {
            content = _.replace(content, '[icds_twig]', '')
            content = _.replace(content, '[/icds_twig]', '')
          }

          return wp.blocks.createBlock(BLOCK_NAME, {
            content: _.trim(content),
          })
        },
      }],
      to: [{
        type: 'block',
        blocks: ['core/html'],
        transform: function(attributes) {
          return wp.blocks.createBlock('core/html', {
            content: '[icds_twig]\n' + attributes.content + '\n[/icds_twig]',
          })
        },
      }],
    },
    edit: function(props) {
      return el(editor, {
        content: props.attributes.content,
        onChange: function(content) {
          props.setAttributes({
            content: content,
          })
        }
      })
    },
    save: function(props) {
      return el(wp.element.RawHTML, null,
        props.attributes.content
      )
    },
  })

}(window, window.wp, window.React, window.lodash))

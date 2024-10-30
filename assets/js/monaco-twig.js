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
(function(window, _, monaco) {

    let twigKeywords = _.map(window.icdsTwigTags, 'name');

    /** @return {Array<CompletionItem>} */
    let twigLanguageCompletionTags = function() {
        return _.map(window.icdsTwigTags, function(tag) {
            return {
                label: tag.name,
                insertText: tag.name,
                kind: monaco.languages.CompletionItemKind.Keyword,
            };
        });
    };

    /** @return {Array<CompletionItem>} */
    let twigLanguageCompletionVarsAndFunctions = function() {
        let arrVars = _.map(window.icdsTwigVars, function(v) {
            return {
                label: v.name,
                insertText: v.name,
                kind: monaco.languages.CompletionItemKind.Variable,
            };
        });

        let arrFunctions = _.map(window.icdsTwigFunctions, function(f) {
            return {
                label: f.name,
                insertText: f.name,
                kind: monaco.languages.CompletionItemKind.Function,
            };
        });

        return _.unionBy(arrVars, arrFunctions, 'label');
    };

    /** @return {Array<CompletionItem>} */
    let twigLanguageCompletionFilters = function() {
        return _.map(window.icdsTwigFilters, function(filter) {
            return {
                label: filter.name,
                insertText: filter.name,
                kind: monaco.languages.CompletionItemKind.Method,
            };
        });
    };

    /** @return {Array<CompletionItem>} */
    let twigLanguageCompletionTagAttributes = function(tagName) {
        let tag = _.find(window.icdsTwigTags, {'name': tagName});

        if (tag) {
            return _.map(tag.attributes, function(attribute) {
                return {
                    label: attribute,
                    insertText: attribute,
                    kind: monaco.languages.CompletionItemKind.Property,
                };
            })
        }

        return null;
    };

    /** @return {Array<CompletionItem>} */
    let twigLanguageCompletionSpecialTagAndAttribute = function(tagName, attributeName, needQuotes) {
        needQuotes = !!needQuotes;

        let tag = _.find(window.icdsTwigTags, {'name': tagName});

        if (tag.name === 'include') {
            let activeVariants = _.filter(window.icdsTwigSpecialTags['include'][''], function(variant) {
                return !variant.disabled;
            });

            return _.map(activeVariants, function(variant) {
                return {
                    label: variant.name,
                    insertText: needQuotes ? quoted(variant.name) : variant.name,
                    kind: monaco.languages.CompletionItemKind.Value,
                };
            });
        }
        else if (tag.name === 'form' && attributeName === 'id') {
            return _.map(window.icdsTwigSpecialTags['form']['id'], function(item) {
                return {
                    label: item.name,
                    insertText: item.id,
                    kind: monaco.languages.CompletionItemKind.Value,
                    detail: '(' + item.id + ')',
                };
            });
        }
        else if (tag.name === 'form' && attributeName === 'mode') {
            return _.map(window.icdsTwigSpecialTags['form']['mode'], function(item) {
                return {
                    label: item.name,
                    insertText: needQuotes ? quoted(item.name) : item.name,
                    kind: monaco.languages.CompletionItemKind.Value,
                };
            });
        }
        else if (tag.name === 'form' && attributeName === 'lookup_filters') {
            return _.map(window.icdsTwigSpecialTags['form']['lookup_filters'], function(item) {
                return {
                    label: item.name,
                    insertText: item.name,
                    kind: monaco.languages.CompletionItemKind.Value,
                };
            });
        }
        else if (tag.name === 'form' && attributeName === 'entity') {
            return _.map(window.icdsTwigSpecialTags['form']['entity'], function(item) {
                return {
                    label: item.name,
                    insertText: needQuotes ? quoted(item.name) : item.name,
                    kind: monaco.languages.CompletionItemKind.Value,
                };
            });
        }
        else if (tag.name === 'form' && attributeName === 'recaptcha') {
            return _.map(window.icdsTwigSpecialTags['form']['recaptcha'], function(item) {
                return {
                    label: item.name,
                    insertText: item.name,
                    kind: monaco.languages.CompletionItemKind.Value,
                };
            });
        }
        else if (tag.name === 'form' && attributeName === 'keep') {
            return _.map(window.icdsTwigSpecialTags['form']['keep'], function(item) {
                return {
                    label: item.name,
                    insertText: item.name,
                    kind: monaco.languages.CompletionItemKind.Value,
                };
            });
        }
        else if (tag.name === 'view' && attributeName === 'entity') {
          return _.map(window.icdsTwigSpecialTags['view']['entity'], function(item) {
            return {
              label: item.name,
              insertText: needQuotes ? quoted(item.name) : item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }
        else if (tag.name === 'view' && attributeName === 'lookups') {
          return _.map(window.icdsTwigSpecialTags['view']['lookups'], function(item) {
            return {
              label: item.name,
              insertText: item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }
        else if (tag.name === 'view' && attributeName === 'parameters') {
          return _.map(window.icdsTwigSpecialTags['view']['parameters'], function(item) {
            return {
              label: item.name,
              insertText: item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }
        else if (tag.name === 'view' && attributeName === 'count') {
          return _.map(window.icdsTwigSpecialTags['view']['count'], function(item) {
            return {
              label: item.name,
              insertText: item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }
        else if (tag.name === 'view' && attributeName === 'pagination_limit') {
          return _.map(window.icdsTwigSpecialTags['view']['pagination_limit'], function(item) {
            return {
              label: item.name,
              insertText: item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }
        else if (tag.name === 'view' && attributeName === 'filter') {
          return _.map(window.icdsTwigSpecialTags['view']['filter'], function(item) {
            return {
              label: item.name,
              insertText: needQuotes ? quoted(item.name) : item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }
        else if (tag.name === 'view' && attributeName === 'filter_parameters') {
          return _.map(window.icdsTwigSpecialTags['view']['filter_parameters'], function(item) {
            return {
              label: item.name,
              insertText: item.name,
              kind: monaco.languages.CompletionItemKind.Value,
            };
          });
        }

        return null;
    };

    /** @return {Array<CompletionItem>} */
    let twigLanguageCompletionVarMembers = function(tokenName) {
        let varr = _.find(window.icdsTwigVars, {'name': tokenName});

        if (varr) {
            return _.map(varr.members, function(member) {
                return {
                    label: member,
                    insertText: member,
                    kind: monaco.languages.CompletionItemKind.Property,
                };
            })
        }

        return null;
    };

    /** @return {Array<SignatureInformation>} */
    let twigLanguageFunctionSignature = function(tokenName) {
        let func = _.find(window.icdsTwigFunctions, {'name': tokenName});

        if (!func) {
            func = _.find(window.icdsTwigFilters, {'name': tokenName});
        }

        if (func) {
            return [{
                label: _.map(func.params, function(param) {
                    return param.name;
                }).join(', '),
                parameters: _.map(func.params, function(param) {
                    return {
                        label: param.name,
                    };
                })
            }];
        }

        return null;
    };


    /** @return {boolean} **/
    let textIsTwigStatementBegin = function(text) {
        let matches = text.match(/{%\s*$/);

        return matches && matches.length > 0;
    };

    /** @return {boolean} **/
    let textIsTwigExpressionBegin = function(text) {
        let matches = text.match(/{{\s*$/);

        return matches && matches.length > 0;
    };

    /** @return {string|null} **/
    let textIsInsideTwigTag = function(text) {
        let matches = text.match(/({%)(\s*)(\w+)((\s+['"]?)|(([\w\W]*?)([^=\s])(\s+)))$/);

        if (matches && matches[3]) {
            return matches[3];
        } else {
            return null;
        }
    };

    /** @return {Object|null} **/
    let textIsInsideTwigTagAttribute = function(text) {
        let matches = text.match(/({%)(\s*)(\w+)[\w\W]*?(\w+)(\s*)(=)([\s'"]*)$/);

        if (matches && matches[4]) {
            return {
                tag: matches[3],
                attribute: matches[4],
                endQuote: matches[7]
            };
        } else {
            return null;
        }
    };

    /** @return {string|null} **/
    let textHasTokenEndedWith = function(text, char) {
        let matches = text.match(new RegExp('({{|{%)([\\w\\W]*?)(\\w*)([' + char + '])$'));

        if (matches && matches[3]) {
            return matches[3];
        } else {
            return null;
        }
    };

    /** @return {Object|null} **/
    let textEndsWithFunction = function(text) {
        let matches = text.match(/({{|{%)([\w\W]*?)[\s|]*(\w+)\(([^)]*)$/);

        if (matches && matches[3]) {
            let parameters = matches[4].match(/,?\s*([^,]*)\s*/g);
            parameters.pop();

            return {
                name: matches[3],
                parameters: parameters,
            };
        } else {
            return null;
        }
    };

    /** @return {string} */
    let filterPrevTags = function(text) {
        let matches = text.match(/({{|{%)[^{]*$/);

        if (matches && matches[0]) {
            return matches[0];
        } else {
            return text;
        }
    };

    /** @return {string} */
    let quoted = function(value) {
        return "'" + value + "'";
    };


    /**
     * Define custom language
     *
     * @param {ILanguageExtensionPoint}
     */
    monaco.languages.register({
        id: 'twigLanguage'
    });

    /**
     * Define custom language configuration
     *
     * @param {string}
     * @param {LanguageConfiguration}
     */
    monaco.languages.setLanguageConfiguration('twigLanguage', {
        comments: {
            blockComment: ['{#', '#}']
        },
        brackets: [
            ['{{', '}}'],
            ['{%', '%}'],
        ],
        autoClosingPairs: [
            {open: '{{', close: ' }}'},
            {open: '{%', close: ' %}'},
            {open: '"', close: '"'},
            {open: "'", close: "'"},
        ],
    });

    /**
     * Define custom language tokenizer
     *
     * @param {string}
     * @param {IMonarchLanguage}
     */
    monaco.languages.setMonarchTokensProvider('twigLanguage', {
        ignoreCase: false,
        defaultToken: '',
        brackets: [
            ['{{', '}}', 'delimiter.expression'],
            ['{%', '%}', 'delimiter.statement'],
            ['{#', '#}', 'comment'],
            //defaults
            ['{', '}', 'delimiter.curly'],
            ['[', ']', 'delimiter.square'],
            ['(', ')', 'delimiter.parenthesis'],
            ['<', '>', 'delimiter.angle'],
        ],

        twigKeywords: twigKeywords,

        tokenizer: {
            // default: HTML
            root: [
                // custom: Twig
                {include: 'twigBrackets'},

                [/<!DOCTYPE/, 'metatag', '@doctype'],
                [/<!--/, 'comment', '@comment'],
                [/(<)((?:[\w\-]+:)?[\w\-]+)(\s*)(\/>)/, ['delimiter', 'tag', '', 'delimiter']],
                [/(<)(script)/, ['delimiter', {token: 'tag', next: '@script'}]],
                [/(<)(style)/, ['delimiter', {token: 'tag', next: '@style'}]],
                [/(<)((?:[\w\-]+:)?[\w\-]+)/, ['delimiter', {token: 'tag', next: '@otherTag'}]],
                [/(<\/)((?:[\w\-]+:)?[\w\-]+)/, ['delimiter', {token: 'tag', next: '@otherTag'}]],
                [/</, 'delimiter'],
                //[/[^<{]+/],
            ],
            doctype: [
                [/[^>]+/, 'metatag.content'],
                [/>/, 'metatag', '@pop'],
            ],
            comment: [
                [/-->/, 'comment', '@pop'],
                [/[^-]+/, 'comment.content'],
                [/./, 'comment.content']
            ],
            otherTag: [
                [/\/?>/, 'delimiter', '@pop'],
                [/"([^"]*)"/, 'attribute.value'],
                [/'([^']*)'/, 'attribute.value'],
                [/[\w\-]+/, 'attribute.name'],
                [/=/, 'delimiter'],
                [/[ \t\r\n]+/],
            ],
            // -- BEGIN <script> tags handling
            // After <script
            script: [
                [/type/, 'attribute.name', '@scriptAfterType'],
                [/"([^"]*)"/, 'attribute.value'],
                [/'([^']*)'/, 'attribute.value'],
                [/[\w\-]+/, 'attribute.name'],
                [/=/, 'delimiter'],
                [/>/, {token: 'delimiter', next: '@scriptEmbedded', nextEmbedded: 'text/javascript'}],
                [/[ \t\r\n]+/],
                [/(<\/)(script\s*)(>)/, ['delimiter', 'tag', {token: 'delimiter', next: '@pop'}]]
            ],
            // After <script ... type
            scriptAfterType: [
                [/=/, 'delimiter', '@scriptAfterTypeEquals'],
                [/>/, {token: 'delimiter', next: '@scriptEmbedded', nextEmbedded: 'text/javascript'}],
                [/[ \t\r\n]+/],
                [/<\/script\s*>/, {token: '@rematch', next: '@pop'}]
            ],
            // After <script ... type =
            scriptAfterTypeEquals: [
                [/"([^"]*)"/, {token: 'attribute.value', switchTo: '@scriptWithCustomType.$1'}],
                [/'([^']*)'/, {token: 'attribute.value', switchTo: '@scriptWithCustomType.$1'}],
                [/>/, {token: 'delimiter', next: '@scriptEmbedded', nextEmbedded: 'text/javascript'}],
                [/[ \t\r\n]+/],
                [/<\/script\s*>/, {token: '@rematch', next: '@pop'}]
            ],
            // After <script ... type = $S2
            scriptWithCustomType: [
                [/>/, {token: 'delimiter', next: '@scriptEmbedded.$S2', nextEmbedded: '$S2'}],
                [/"([^"]*)"/, 'attribute.value'],
                [/'([^']*)'/, 'attribute.value'],
                [/[\w\-]+/, 'attribute.name'],
                [/=/, 'delimiter'],
                [/[ \t\r\n]+/],
                [/<\/script\s*>/, {token: '@rematch', next: '@pop'}]
            ],
            scriptEmbedded: [
                [/<\/script/, {token: '@rematch', next: '@pop', nextEmbedded: '@pop'}],
                [/[^<]+/, '']
            ],
            // -- END <script> tags handling
            // -- BEGIN <style> tags handling
            // After <style
            style: [
                [/type/, 'attribute.name', '@styleAfterType'],
                [/"([^"]*)"/, 'attribute.value'],
                [/'([^']*)'/, 'attribute.value'],
                [/[\w\-]+/, 'attribute.name'],
                [/=/, 'delimiter'],
                [/>/, {token: 'delimiter', next: '@styleEmbedded', nextEmbedded: 'text/css'}],
                [/[ \t\r\n]+/],
                [/(<\/)(style\s*)(>)/, ['delimiter', 'tag', {token: 'delimiter', next: '@pop'}]]
            ],
            // After <style ... type
            styleAfterType: [
                [/=/, 'delimiter', '@styleAfterTypeEquals'],
                [/>/, {token: 'delimiter', next: '@styleEmbedded', nextEmbedded: 'text/css'}],
                [/[ \t\r\n]+/],
                [/<\/style\s*>/, {token: '@rematch', next: '@pop'}]
            ],
            // After <style ... type =
            styleAfterTypeEquals: [
                [/"([^"]*)"/, {token: 'attribute.value', switchTo: '@styleWithCustomType.$1'}],
                [/'([^']*)'/, {token: 'attribute.value', switchTo: '@styleWithCustomType.$1'}],
                [/>/, {token: 'delimiter', next: '@styleEmbedded', nextEmbedded: 'text/css'}],
                [/[ \t\r\n]+/],
                [/<\/style\s*>/, {token: '@rematch', next: '@pop'}]
            ],
            // After <style ... type = $S2
            styleWithCustomType: [
                [/>/, {token: 'delimiter', next: '@styleEmbedded.$S2', nextEmbedded: '$S2'}],
                [/"([^"]*)"/, 'attribute.value'],
                [/'([^']*)'/, 'attribute.value'],
                [/[\w\-]+/, 'attribute.name'],
                [/=/, 'delimiter'],
                [/[ \t\r\n]+/],
                [/<\/style\s*>/, {token: '@rematch', next: '@pop'}]
            ],
            styleEmbedded: [
                [/<\/style/, {token: '@rematch', next: '@pop', nextEmbedded: '@pop'}],
                [/[^<]+/, '']
            ],

            // custom: Twig
            twigBrackets: [
                [/{{/, {token: '@rematch', switchTo: '@twigExpression'}],
                [/{%/, {token: '@rematch', switchTo: '@twigStatement'}],
                [/{#/, {token: '@rematch', switchTo: '@twigComment'}],
            ],
            twigExpression: [
                [/{{/, {token: 'delimiter.expression', next: '@twigScriptEmbedded', nextEmbedded: 'text/javascript'}],
                [/}}/, {token: 'delimiter.expression', switchTo: '@root'}],
                {include: 'twigCommon'},
            ],
            twigStatement: [
                [/{%/, 'delimiter.statement'],
                [/%}/, {token: 'delimiter.statement', switchTo: '@root'}],
                {include: 'twigCommon'},
            ],
            twigComment: [
                [/{#/, 'comment'],
                [/((?!#}).)+/, 'comment.content'],
                [/#}/, {token: 'comment', switchTo: '@root'}],
            ],
            twigCommon: [
                [/([a-z_][\w]*)(\s*=\s*)([^\s(]*\()/, [
                    {token: 'twig.attribute.name'},
                    {token: 'delimiter'},
                    {token: 'twig.attribute.value', next: '@twigAttributeParams'},
                ]],
                [/([a-z_][\w]*)(\s*=\s*)([^\s'"{]*)/, [
                    {token: 'twig.attribute.name'},
                    {token: 'delimiter'},
                    {token: 'twig.attribute.value'},
                ]],
                [/({)([^}]*)(})/, [
                    {token: 'twig.attribute.value'},
                    {token: 'twig.attribute.value'},
                    {token: 'twig.attribute.value'},
                ]],
                [/(['"])([^'"]*)(['"])/, [
                    {token: 'twig.attribute.value'},
                    {token: 'twig.attribute.value'},
                    {token: 'twig.attribute.value'},
                ]],
                [/[a-z][\w]*/, {
                    cases: {
                        '@twigKeywords': {token: 'twig.keyword'},
                        '@default': {token: 'twig.attribute.name'},
                    }
                }],
            ],
            twigScriptEmbedded: [
                [/}}/, {token: '@rematch', next: '@pop', nextEmbedded: "@pop"}],
                [/[^}]+/, ''],
            ],
            twigAttributeParams: [
                [/(\))/, {token: 'twig.attribute.value', next: '@pop'}],
                [/[^),]+/, {token: 'twig.attribute.params'}],
                [/ ?, ?/, {token: 'twig.attribute.value'}],
            ],
        }
    });

    /**
     * Define custom language completion list
     *
     * @param {string}
     * @param {CompletionItemProvider}
     */
    monaco.languages.registerCompletionItemProvider('twigLanguage', {
        triggerCharacters: ['%', '{', '.', '(', '|', ' ', '=', '"', "'"],
        provideCompletionItems: function(model, position, context) {
            let textBefore = model.getValueInRange({
                startLineNumber: 1,
                startColumn: 1,
                endLineNumber: position.lineNumber,
                endColumn: position.column
            });

            textBefore = filterPrevTags(textBefore);

            /** @type {Array<CompletionItem>} */
            let suggestions = [];
            let currentToken;

            if ((currentToken = textHasTokenEndedWith(textBefore, '.')) !== null) {
                suggestions = twigLanguageCompletionVarMembers(currentToken);
            }
            else if (textHasTokenEndedWith(textBefore, '|')) {
                suggestions = twigLanguageCompletionFilters();
            }
            else if (textIsTwigStatementBegin(textBefore)) {
                suggestions = twigLanguageCompletionTags();
            }
            else if ((currentToken = textIsInsideTwigTag(textBefore)) !== null) {
                suggestions = twigLanguageCompletionSpecialTagAndAttribute(currentToken) ||
                    twigLanguageCompletionTagAttributes(currentToken);
            }
            else if ((currentToken = textIsInsideTwigTagAttribute(textBefore)) !== null) {
                suggestions = twigLanguageCompletionSpecialTagAndAttribute(
                    currentToken.tag,
                    currentToken.attribute,
                    currentToken.endQuote.length === 0
                    ) ||
                    twigLanguageCompletionVarsAndFunctions();
            }
            else if (textIsTwigExpressionBegin(textBefore)) {
                suggestions = twigLanguageCompletionVarsAndFunctions();
            }

            return {
                suggestions: suggestions
            };
        }
    });

    /**
     * Define custom language signature help list
     *
     * @param {string}
     * @param {SignatureHelpProvider}
     */
    monaco.languages.registerSignatureHelpProvider('twigLanguage', {
        signatureHelpTriggerCharacters: ['(', ','],
        provideSignatureHelp: function(model, position) {
            let textBefore = model.getValueInRange({
                startLineNumber: 1,
                startColumn: 1,
                endLineNumber: position.lineNumber,
                endColumn: position.column
            });

            /** @type {Array<SignatureInformation>} */
            let signatures = [];
            let activeParameter = 0;
            let currentFunction;

            if ((currentFunction = textEndsWithFunction(textBefore)) !== null) {
                signatures = twigLanguageFunctionSignature(currentFunction.name);
                activeParameter = currentFunction.parameters.length ? currentFunction.parameters.length - 1 : 0;
            }

            return {
                signatures: signatures,
                activeSignature: 0,
                activeParameter: activeParameter,
            }
        }
    });

    /**
     * Define custom theme
     *
     * @param {string}
     * @param {IStandaloneThemeData}
     */
    monaco.editor.defineTheme('twigTheme', {
        base: "vs",
        inherit: true,
        rules: [
            {token: 'delimiter.expression', foreground: '#547494', fontStyle: 'bold'},
            {token: 'delimiter.statement', foreground: '#539494', fontStyle: 'bold'},
            {token: 'twig.keyword', foreground: '#547494', fontStyle: 'bold'},
            {token: 'twig.attribute.name', foreground: '#381B93'},
            {token: 'twig.attribute.value', foreground: '#DA7A2E'},
            {token: 'twig.attribute.params', foreground: '#777777'},
        ]
    });

}(window, window.lodash, window.monaco));

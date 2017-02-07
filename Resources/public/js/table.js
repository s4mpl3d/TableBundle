var whatwedoTable = {
    /**
     * Anklickbare Tabellenzeilen
     */
    clickableRows: function($whatwedoTable) {
        $(document).on('click', '#whatwedo_table tr[data-href]', function(e) {
            var $this = $(this);

            if (!$this.closest('#whatwedo_table').hasClass('whatwedo_table__editable')) {
                if($(e.target).is('td')) {
                    window.document.location = $this.data("href");
                }
            }
        });
    },

    /**
     * Auswählbare Tabellenzeilen
     */
    selectableRows: function($whatwedoTable) {
        var $whatwedoTableSelectedTbody = $('#whatwedo_table_selected tbody');
        var $whatwedoTableTbody = $('#whatwedo_table tbody');

        $(document).on('change', 'input[data-multiselect]', function() {
            var $this = $(this);
            var isInSelectedTable = $this.parents('#whatwedo_table_selected').length == 1;
            var inputSelector = 'input[data-multiselect="this"][value="' + $this.val() + '"]';

            switch ($this.data('multiselect')) {
                case "all":
                    if ($this.is(':checked')) {
                        $this.closest('table').find('input[data-multiselect="this"]').prop('checked', true);
                        $('input[data-multiselect="this"]').trigger('change');
                    } else {
                        $this.closest('table').find('input[data-multiselect="this"]').prop('checked', false);
                        $('input[data-multiselect="this"]').trigger('change');
                    }
                    break;
                case "this":
                    if ($this.is(':checked')) {
                        $this.closest('tr').clone().appendTo($whatwedoTableSelectedTbody).find('.whawtedo_table__row_operations').remove();
                    } else {
                        $whatwedoTableSelectedTbody.find(inputSelector).closest('tr').remove();
                        $whatwedoTableTbody.find(inputSelector).prop('checked', false);
                    }
                    break;
            }

            if ($whatwedoTableSelectedTbody.find('tr').length > 0) {
                $('#whatwedo_table_selected').show();
            } else {
                $('#whatwedo_table_selected').hide();
            }
        });
        $('input[data-multiselect]').trigger('change');
    },

    /**
     * current ongoing AJAX request
     */
    currentRequest: null,

    /**
     * loads updated data from the database
     * @param url
     * @param data
     * @param failCallback
     * @param method
     * @param animate
     */
    loadContent: function(url, data, failCallback, method, animate, replace, showLoad, callback) {
        if (typeof data === 'undefined') {
            data = null;
        }

        if (typeof method === 'undefined') {
            method = 'GET';
        }

        if (typeof animate === 'undefined') {
            animate = true;
        }

        if (typeof replace === 'undefined') {
            replace = true;
        }

        if (typeof showLoad === 'undefined') {
            showLoad = true;
        }

        if (typeof callback === 'undefined') {
            callback = jQuery.noop;
        }

        var $whatwedoTable = $('#whatwedo_table');

        if (showLoad) {
            $whatwedoTable.addClass('loading');
        }

        if (whatwedoTable.currentRequest !== null) {
            whatwedoTable.currentRequest.abort();
        }

        this.currentRequest = $.ajax({
            url: url,
            data: data,
            method: method
        })
            .done(function(html) {
                callback(html);

                if (!replace) {
                    return;
                }

                history.pushState({
                    'type': 'table',
                    'url': url,
                    'data': data
                }, null, url);

                $whatwedoTable.replaceWith(html);
                whatwedoTable.tableHeader();

                if (animate) {
                    $("html, body").animate({ scrollTop: $('#whatwedo_table').offset().top - 100 }, 250);
                }
            })
            .fail(failCallback)
            .always(function() {
                whatwedoTable.currentRequest = null;
            });
    },

    handleDataLoadEnabled: true,

    handleDataLoad: function(event) {
        if (!whatwedoTable.handleDataLoadEnabled) {
            return;
        }

        var href = this.getAttribute('href');
        var $this = $(this);

        event.preventDefault();

        // Bugfix for Firefox
        var additionalName = null;
        var additionalValue = null;
        var action = null;

        console.log('prop', $this.prop("tagName"));
        if ($this.prop("tagName") !== 'FORM') {
            if ($this.prop("tagName") === 'BUTTON') {
                additionalName = $this.attr('name');
                additionalValue = $this.attr('value');
            }
            if ($this.prop("tagName") === 'A') {
                action = $this.attr('href');
            } else {
                $this = $($this.parents('form'));
                action = $this.attr('action');
            }
        } else {
            if (typeof $this.attr('action') !== 'undefined') {
                action = $this.attr('action');
                console.log('action', $this.attr('action'));
            }
        }

        $this.find('input[data-handle-data-load]').remove();

        if (action) {
            var formParams = {};
            $this
                .serializeArray()
                .forEach(function(item) {
                    if (formParams[item.name]) {
                        formParams[item.name] = [formParams[item.name]];
                        formParams[item.name].push(item.value)
                    } else {
                        formParams[item.name] = item.value
                    }
                });

            if (typeof additionalName !== 'undefined'
                && typeof additionalValue !== 'undefined'
                && additionalName
                && additionalValue) {
                $('<input/>')
                    .attr('type', 'hidden')
                    .attr('data-handle-data-load', 1)
                    .attr('name', additionalName)
                    .attr('value', additionalValue)
                    .appendTo($this);
                formParams[additionalName] = additionalValue;
            }

            if (typeof formParams['filter_name'] !== 'undefined') {
                $this.submit();
                return;
            }

            whatwedoTable.loadContent(action, $this.serialize(), function() {
                $this.submit();
            });
        }
        else if (typeof $this.attr('href') !== 'undefined') {
            whatwedoTable.loadContent($this.attr('href'), null, function() {
                window.location.href = $this.attr('href');
            });
        }
        else if (typeof href !== 'undefined') {
            whatwedoTable.loadContent(href, null, function() {
                window.location.href = href;
            });
        }
    },

    addHandlers: function($whatwedoTable) {
        $(document).on('click', '#whatwedo_table th a', this.handleDataLoad);
        $(document).on('click', '#whatwedo_table .whatwedo_table-pagination a', this.handleDataLoad);
        $(document).on('submit', '#whatwedo_table .whatwedo_table-search form', this.handleDataLoad);
        $(document).on('change', '#whatwedo_table .whatwedo_table-search select', function() { $(this).submit(); });
        $(document).on('click', '#whatwedo_table .whatwedo_table-search a', this.handleDataLoad);
        $(document).on('click', '#whatwedo_table__filters [type="submit"]', this.handleDataLoad);
        window.addEventListener('popstate', function(e){
            if (e.state.type === 'table') {
                whatwedoTable.loadContent(e.state.url, e.state.data);
            }
        });
    },

    filters: function() {
        // Template
        var filterTemplate = $('#whatwedo_table__filters__template__block').text();

        // jQuery Elemente
        var $whatwedoTableFilters = null;

        // Functiosn
        var optionNameMatcher = /filter_([\w\d]+)\[(\d)\]\[(\d)\]/i;

        var findCurrentBlocksBlockIteratorNumber = function($blocksContainer) {
            var optionName = $blocksContainer.find('.whatwedo_table__filters__block:last select:first').attr('name');
            if (!optionNameMatcher.test(optionName)) {
                return 0;
            }
            var result = optionNameMatcher.exec(optionName);

            result = parseInt(result[3]);
            if (isNaN(result)) {
                return 0;
            }

            return result;
        };

        var findCurrentBlockIteratorNumber = function($blocksContainer) {
            var optionName = $blocksContainer.find('.whatwedo_table__filters__block:last select:first').attr('name');
            if (!optionNameMatcher.test(optionName)) {
                return 0;
            }
            var result = optionNameMatcher.exec(optionName);

            result = parseInt(result[2]);
            if (isNaN(result)) {
                return 0;
            }

            return result;
        };

        $(document).on('click', '#whatwedo_table [data-filter-action="add-and"]', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $blocksContainer = $this.closest('.whatwedo_table__filters__blocks');
            var currentBlockIteratorNumber = findCurrentBlockIteratorNumber($blocksContainer);
            var currentBlocksBlockIteratorNumber = findCurrentBlocksBlockIteratorNumber($blocksContainer);
            var block = filterTemplate
                .replace(/{iBlock}/g, currentBlockIteratorNumber.toString())
                .replace(/{i}/g, (currentBlocksBlockIteratorNumber + 1).toString());
            $blocksContainer.append(block);
        });

        $(document).on('click', '#whatwedo_table [data-filter-action="add-or"]', function(e) {
            e.preventDefault();
            var $lastBlocksContainer = $('#whatwedo_table__filters').find('.whatwedo_table__filters__blocks:last');
            var currentBlockIteratorNumber = findCurrentBlockIteratorNumber($lastBlocksContainer);
            var block = filterTemplate
                .replace(/{iBlock}/g, (currentBlockIteratorNumber + 1).toString())
                .replace(/{i}/g, '1');
            block = '<div class="whatwedo_table__filters__blocks"><p><strong>oder</strong></p>' + block + '</div>';

            $lastBlocksContainer.after(block);
        });

        $(document).on('click', '#whatwedo_table [data-filter-action="remove"]', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $block = $this.closest('.whatwedo_table__filters__block');

            var $blocksContainer = $block.closest('.whatwedo_table__filters__blocks');

            $block.remove();

            if ($blocksContainer.find('.whatwedo_table__filters__block').length === 0) {
                $blocksContainer.remove();
                $('#whatwedo_table__filters').submit();
            }
        });

        $(document).on('click', '#whatwedo_table [data-toggle="filter"]', function() {
            var $whatwedoTableFilters = $('#whatwedo_table__filters');

            if ($whatwedoTableFilters.hasClass('active')) {
                $whatwedoTableFilters.slideUp();
                $whatwedoTableFilters.removeClass('active')
            } else {
                $whatwedoTableFilters.slideDown();
                $whatwedoTableFilters.addClass('active');
                var filter_value = $('.whatwedo_table__filters_filter [name^="filter_value"]');
                if (filter_value.is('select')) {
                    $('.whatwedo_table__filters_filter [name^="filter_value"]').select2();
                }
            }
        });

        $(document).on('change', '#whatwedo_table select[name^="filter_column"]', function() {
            var $this = $(this);
            var $parentBlock = $this.parents('.whatwedo_table__filters_filter');
            var $choosenOption = $this.find(":selected");

            // Operator
            var $operator = $parentBlock.find('select[name^="filter_operator"]');
            $operator.empty();
            $.each($choosenOption.data('operator-options'), function(key, name) {
                $operator.append('<option value=' + key + '>' + name + '</option>')
            });

            // Field
            var $field = $parentBlock.find('[name^="filter_value"]');

            if (typeof $field.data('select2') !== 'undefined') {
                $field.select2('destroy');
            }
            var fieldName = $field.attr('name');
            var template = $choosenOption.data('value-template');
            $field.replaceWith(template.replace(/{name}/g, fieldName));
            $field = $parentBlock.find('[name^="filter_value"]');

            if ($field.prop('tagName') == 'SELECT'
                && typeof $field.attr('data-disable-interactive') === 'undefined') {
                $field.select2({
                    language: 'de',
                    width: '100%'
                });
            }
        });
    },

    tableHeader: function() {
        $('table[data-fixed-header]').stickyTableHeaders({
            fixedOffset: 105
        });
    },

    setLimit: function() {
      var buildUrl = function(base, key, value) {
        var sep = (base.indexOf('?') > -1) ? '&' : '?';
        return base + sep + key + '=' + value;
      };

      $('#whatwedo_table select[name="limit"]').change(function(e) {
            window.location.href = buildUrl(buildUrl(window.location.href, 'page', 1), 'limit', $(this).val());
            e.preventDefault();
        });
    },

    /**
     * initialize class
     */
    init: function() {
        this.clickableRows();
        //this.selectableRows();
        //this.addHandlers();
        this.filters();
        this.tableHeader();
        this.setLimit();
    }
};

$(document).ready(function() {
    whatwedoTable.init();
});

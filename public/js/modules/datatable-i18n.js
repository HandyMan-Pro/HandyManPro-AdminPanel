(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    var namespace = '.appDataTableI18n';
    var maxRetries = 30;
    var retryDelayMs = 100;
    var retryCount = 0;

    function getConfig() {
        var cfg = window.AppDataTableI18n || {};
        return {
            searchLabel: cfg.searchLabel || 'Search',
            searchPlaceholderRaw: cfg.searchPlaceholderRaw || 'Search...',
            displayEntries: cfg.displayEntries || 'Show _MENU_ entries',
            showingInfo: cfg.showingInfo || '_START_ to _END_ of _TOTAL_ entries',
            emptyTable: cfg.emptyTable || 'No data available in table',
            noDataFound: cfg.noDataFound || 'No data found',
            previousLabel: cfg.previousLabel || 'Previous',
            nextLabel: cfg.nextLabel || 'Next',
            processingText: cfg.processingText || 'processing...'
        };
    }

    function normalizeSearchPlaceholder(text) {
        var value = (text || '').trim();

        if (!value) {
            return 'Search...';
        }

        if (value.indexOf('...') !== -1) {
            return value;
        }

        return value.replace(/:+$/g, '') + '...';
    }

    function getModernLanguage(cfg) {
        return {
            processing: cfg.processingText,
            search: cfg.searchLabel,
            searchPlaceholder: normalizeSearchPlaceholder(cfg.searchPlaceholderRaw || cfg.searchLabel),
            lengthMenu: cfg.displayEntries,
            info: cfg.showingInfo,
            infoEmpty: cfg.showingInfo,
            emptyTable: cfg.emptyTable,
            zeroRecords: cfg.noDataFound,
            paginate: {
                previous: cfg.previousLabel,
                next: cfg.nextLabel
            }
        };
    }

    function getLegacyLanguage(cfg) {
        return {
            sProcessing: cfg.processingText,
            sSearch: cfg.searchLabel,
            sSearchPlaceholder: normalizeSearchPlaceholder(cfg.searchPlaceholderRaw || cfg.searchLabel),
            sLengthMenu: cfg.displayEntries,
            sInfo: cfg.showingInfo,
            sInfoEmpty: cfg.showingInfo,
            sEmptyTable: cfg.emptyTable,
            sZeroRecords: cfg.noDataFound,
            oPaginate: {
                sPrevious: cfg.previousLabel,
                sNext: cfg.nextLabel
            }
        };
    }

    function applySearchInputText($scope, cfg) {
        var placeholder = normalizeSearchPlaceholder(cfg.searchPlaceholderRaw || cfg.searchLabel);

        $scope.find('.dt-search, .dataTables_filter input[type="search"]').attr({
            placeholder: placeholder,
            'aria-label': cfg.searchLabel
        });
    }

    function applyLengthMenuText($scope, cfg) {
        $scope.find('.dataTables_length label').each(function () {
            var $label = $(this);
            var $select = $label.find('select');

            if (!$select.length) {
                return;
            }

            var template = (cfg.displayEntries || 'Show _MENU_ entries').trim();
            if (template.indexOf('_MENU_') === -1) {
                template += ' _MENU_';
            }

            var parts = template.split('_MENU_');
            var before = (parts[0] || '').trim();
            var after = (parts.slice(1).join('_MENU_') || '').trim();

            $label.empty();

            if (before) {
                $label.append(document.createTextNode(before + ' '));
            }

            $label.append($select);

            if (after) {
                $label.append(document.createTextNode(' ' + after));
            }
        });
    }

    function applyInfoText($scope, settings, cfg) {
        if (!settings || !$.fn || !$.fn.dataTable || !$.fn.dataTable.Api) {
            return;
        }

        var api = new $.fn.dataTable.Api(settings);
        var pageInfo = api.page.info();

        var start = pageInfo.recordsDisplay > 0 ? (pageInfo.start + 1) : 0;
        var end = pageInfo.end;
        var total = pageInfo.recordsDisplay;

        var template = cfg.showingInfo || '_START_ to _END_ of _TOTAL_ entries';
        var text = template
            .replace(/_START_/g, String(start))
            .replace(/_END_/g, String(end))
            .replace(/_TOTAL_/g, String(total));

        $scope.find('.dataTables_info').text(text);
    }

    function bindLifecycleEvents(cfg) {
        var modern = getModernLanguage(cfg);
        var legacy = getLegacyLanguage(cfg);

        $(document)
            .off('preInit.dt' + namespace)
            .off('init.dt' + namespace)
            .off('draw.dt' + namespace)
            .off('change' + namespace, '.dataTables_length select')
            .on('preInit.dt' + namespace, function (e, settings) {
                settings.oLanguage = settings.oLanguage || {};
                $.extend(true, settings.oLanguage, legacy);

                settings.oInit = settings.oInit || {};
                settings.oInit.language = settings.oInit.language || {};
                $.extend(true, settings.oInit.language, modern);
            })
            .on('init.dt' + namespace + ' draw.dt' + namespace, function (e, settings) {
                var $scope = settings && settings.nTableWrapper ? $(settings.nTableWrapper) : $(document);
                applySearchInputText($scope, cfg);
                applyLengthMenuText($scope, cfg);
                applyInfoText($scope, settings, cfg);
            })
            .on('change' + namespace, '.dataTables_length select', function () {
                var length = parseInt(this.value, 10);
                if (!length || !$.fn || !$.fn.dataTable) {
                    return;
                }

                var $wrapper = $(this).closest('.dataTables_wrapper');
                var $table = $wrapper.find('table.dataTable').first();
                if (!$table.length || !$.fn.dataTable.isDataTable($table[0])) {
                    return;
                }

                $($table[0]).DataTable().page.len(length).draw(false);
            });
    }

    function applyDefaultsIfAvailable(cfg) {
        if (!$.fn || !$.fn.dataTable || !$.fn.dataTable.defaults) {
            return false;
        }

        var modern = getModernLanguage(cfg);
        var legacy = getLegacyLanguage(cfg);

        $.extend(true, $.fn.dataTable.defaults, {
            language: modern,
            oLanguage: legacy
        });

        return true;
    }

    function ensureDefaults(cfg) {
        if (applyDefaultsIfAvailable(cfg)) {
            return;
        }

        if (retryCount >= maxRetries) {
            return;
        }

        retryCount += 1;
        window.setTimeout(function () {
            ensureDefaults(cfg);
        }, retryDelayMs);
    }

    function init() {
        var cfg = getConfig();

        bindLifecycleEvents(cfg);
        ensureDefaults(cfg);

        applySearchInputText($(document), cfg);
        applyLengthMenuText($(document), cfg);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document, window.jQuery);

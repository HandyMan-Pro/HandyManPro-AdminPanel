import { onMounted, onBeforeUnmount } from 'vue'
import $ from 'jquery'
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css'
import 'datatables.net-bs5'

const htmlLocale = document?.documentElement?.lang?.split('-')[0]
const currentLocale = sessionStorage.getItem('local') || htmlLocale || 'en'

const languageFiles = {
  ar: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json',
  nl: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Dutch.json',
  de: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/German.json',
  hi: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Hindi.json',
  en: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/English.json',
  fr: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/French.json',
  it: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Italian.json',
  pt: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese.json',
  es: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
}

const useDataTable = ({
  tableRef,
  columns,
  data = [],
  url = null,
  message = '',              // Default to empty string
  actionCallback,
  per_page = 10,
  advanceFilter = undefined,
  isTable = false,
  ordering = true,           // Enable/disable sorting globally
  dom = '<"row align-items-center"<"col-md-6" l><"col-md-6" f>><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" i><"col-md-6" p>><"clear">'
}) => {
  onMounted(async () => {
    setTimeout(async () => {
      let languageSettings = {}

      //   const languageUrl = languageFiles[currentLocale] || languageFiles['en'];
      //   const lat = localStorage.getItem('loction_current_lat');
      //   const long = localStorage.getItem('loction_current_long');

      //   let noDataMessage = 'No data available in the table';

      //   if (lat != '' && long != '') {
      //     noDataMessage = message ? message : 'Currently, there are no data available in this zone';
      //   }

      const languageUrl = languageFiles[currentLocale] || languageFiles['en'];
      const lat = localStorage.getItem('loction_current_lat');
      const long = localStorage.getItem('loction_current_long');

      const noDataInTable = window.i18n?.global?.t?.('landingpage.no_data_available_in_table') ?? 'No data available in the table'
      const noDataInZone = window.i18n?.global?.t?.('landingpage.no_data_available_in_zone') ?? 'Currently, there are no data available in this zone'
      let noDataMessage = noDataInTable

      // Check if location data is available or not
      if (lat && long) {
        // Location data exists (location is on)
        noDataMessage = message ? message : noDataInZone
      } else {
        // Location data does not exist (location is off)
        noDataMessage = message ? message : noDataInTable
      }


      try {
        const res = await fetch(languageUrl)
        languageSettings = await res.json()
        const displayEntries = window.i18n?.global?.t?.('landingpage.display_entries') ?? 'Display _MENU_ entries'
        const showingInfo = window.i18n?.global?.t?.('landingpage.showing_info') ?? '_START_ to _END_ of _TOTAL_ entries'
        languageSettings.info = showingInfo
        languageSettings.lengthMenu = displayEntries
        languageSettings.emptyTable = noDataMessage
      } catch (err) {
        const displayEntries = window.i18n?.global?.t?.('landingpage.display_entries') ?? 'Display _MENU_ entries'
        const showingInfo = window.i18n?.global?.t?.('landingpage.showing_info') ?? '_START_ to _END_ of _TOTAL_ entries'
        languageSettings = {
          info: showingInfo,
          lengthMenu: displayEntries
        }
      }

      let datatableObj = {
        dom: dom,
        autoWidth: false,
        columns: columns,
        language: languageSettings,
        ordering: ordering,

        initComplete: function () {
          const api = this.api()
          const pageInfo = api.page.info()
          const totalEntries = pageInfo.recordsTotal

          // Inject custom message next to length menu
          const lengthMenuContainer = $(this)
            .closest('.dataTables_wrapper')
            .find('.dataTables_length label')

          const showingText = window.i18n?.global?.t?.('landingpage.showing_entries', { start: 1, end: pageInfo.end, total: totalEntries }) ?? `Showing 1 to ${pageInfo.end} of ${totalEntries} entries`
          lengthMenuContainer.append(
            `<span class="ms-2 text-muted custom-length-info"> ${showingText}</span>`
          )

          // Custom styling for tbody
          if (!isTable) {
            if (tableRef.value.id === 'helpdesk-datatable') {
              $(tableRef.value)
                .find('tbody')
                .addClass('row row-cols-xl-3 row-cols-lg-3 row-cols-sm-2')
            } else {
              $(tableRef.value)
                .find('tbody')
                .addClass('row row-cols-xl-4 row-cols-lg-3 row-cols-sm-2')
            }
          }
        },

        drawCallback: function () {
          const api = this.api()
          const pageInfo = api.page.info()
          const totalEntries = pageInfo.recordsTotal

          const lengthInfoSpan = $(this)
            .closest('.dataTables_wrapper')
            .find('.dataTables_length .custom-length-info')

          if (lengthInfoSpan.length) {
            const showingText = window.i18n?.global?.t?.('landingpage.showing_entries', { start: pageInfo.start + 1, end: pageInfo.end, total: totalEntries }) ?? `Showing ${pageInfo.start + 1} to ${pageInfo.end} of ${totalEntries} entries`
            lengthInfoSpan.text(` ${showingText}`)
          }
        }
      }

      if (url) {
        datatableObj = {
          ...datatableObj,
          processing: true,
          serverSide: true,
          pageLength: per_page,
          ajax: {
            url: url,
            data: function (d) {
              if (typeof advanceFilter === 'function' && advanceFilter() !== undefined) {
                d.filter = { ...d.filter, ...advanceFilter() }
              }
            }
          }
        }
      }

      if (data.length) {
        datatableObj = {
          ...datatableObj,
          data: data
        }
      }

      let datatable = $(tableRef.value).DataTable(datatableObj)

      if (typeof actionCallback === 'function') {
        $(datatable.table().body()).on('click', '[data-table="action"]', function () {
          actionCallback({
            id: $(this).data('id'),
            method: $(this).data('method')
          })
        })
      }
    }, 0)
  })

  onBeforeUnmount(() => {
    if ($.fn.DataTable.isDataTable(tableRef.value)) {
      $(tableRef.value).DataTable().destroy()
    }
    $(tableRef.value).empty()
  })
}

export default useDataTable

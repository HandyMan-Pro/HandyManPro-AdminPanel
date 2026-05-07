<x-master-layout>
<style>
/**
 * Backend custom styles (shop hours, Flatpickr dark mode, etc.)
 * Uses project CSS variables; same rules in backend-custom.scss.
 */

/* ----- Flatpickr time picker: dark mode (AM/PM visible) ----- */
[data-bs-theme="dark"] .flatpickr-calendar {
  background: var(--bs-body-bg) !important;
  border: 1px solid var(--bs-border-color) !important;
  box-shadow: 0 3px 13px rgba(var(--bs-black-rgb), 0.3);
}
[data-bs-theme="dark"] .flatpickr-calendar.arrowTop:after {
  border-bottom-color: var(--bs-body-bg) !important;
}
[data-bs-theme="dark"] .flatpickr-calendar.arrowBottom:after {
  border-top-color: var(--bs-body-bg) !important;
}
[data-bs-theme="dark"] .flatpickr-calendar.hasTime .flatpickr-time {
  border-top-color: var(--bs-border-color) !important;
}
[data-bs-theme="dark"] .flatpickr-time input,
[data-bs-theme="dark"] .flatpickr-time .flatpickr-time-separator,
[data-bs-theme="dark"] .flatpickr-time .flatpickr-am-pm {
  color: var(--bs-body-color) !important;
}
[data-bs-theme="dark"] .flatpickr-time input:hover,
[data-bs-theme="dark"] .flatpickr-time .flatpickr-am-pm:hover,
[data-bs-theme="dark"] .flatpickr-time input:focus,
[data-bs-theme="dark"] .flatpickr-time .flatpickr-am-pm:focus {
  background: var(--bs-tertiary-bg) !important;
}
[data-bs-theme="dark"] .flatpickr-time .numInputWrapper span.arrowUp:after {
  border-bottom-color: var(--bs-body-color) !important;
}
[data-bs-theme="dark"] .flatpickr-time .numInputWrapper span.arrowDown:after {
  border-top-color: var(--bs-body-color) !important;
}
[data-bs-theme="dark"] .flatpickr-time .numInputWrapper span {
  border-color: rgba(var(--bs-white-rgb), 0.2);
}
[data-bs-theme="dark"] .flatpickr-time .numInputWrapper:hover span {
  opacity: 1;
}

body.dark .flatpickr-calendar {
  background: var(--bs-body-bg) !important;
  border: 1px solid var(--bs-border-color) !important;
}
body.dark .flatpickr-calendar.hasTime .flatpickr-time {
  border-top-color: var(--bs-border-color) !important;
}
body.dark .flatpickr-time input,
body.dark .flatpickr-time .flatpickr-time-separator,
body.dark .flatpickr-time .flatpickr-am-pm {
  color: var(--bs-body-color) !important;
}
body.dark .flatpickr-time input:hover,
body.dark .flatpickr-time .flatpickr-am-pm:hover,
body.dark .flatpickr-time input:focus,
body.dark .flatpickr-time .flatpickr-am-pm:focus {
  background: var(--bs-tertiary-bg) !important;
}
body.dark .flatpickr-time .numInputWrapper span.arrowUp:after {
  border-bottom-color: var(--bs-body-color) !important;
}
body.dark .flatpickr-time .numInputWrapper span.arrowDown:after {
  border-top-color: var(--bs-body-color) !important;
}

.flatpickr-calendar.flatpickr-dark-mode {
  background: var(--bs-body-bg) !important;
}
.flatpickr-calendar.flatpickr-dark-mode .flatpickr-time input,
.flatpickr-calendar.flatpickr-dark-mode .flatpickr-time .flatpickr-time-separator,
.flatpickr-calendar.flatpickr-dark-mode .flatpickr-time .flatpickr-am-pm {
  color: var(--bs-body-color) !important;
}
.flatpickr-calendar.flatpickr-dark-mode .flatpickr-time .numInputWrapper span.arrowUp:after {
  border-bottom-color: var(--bs-body-color) !important;
}
.flatpickr-calendar.flatpickr-dark-mode .flatpickr-time .numInputWrapper span.arrowDown:after {
  border-top-color: var(--bs-body-color) !important;
}

/* ----- Shop hours: Add Break button (minimal style) ----- */
.add-break-btn .add-break-icon {
  width: 1rem;
  height: 1rem;
  font-size: 0.65rem;
}
.add-break-btn:hover {
  color: var(--bs-secondary) !important;
}
</style>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch mb-4">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold mb-0">
                                {{ __('messages.business_hours') }}
                            </h5>
                            <a href="{{ route('shop.index') }}" class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('shop.manage-hour.store', $shop->id) }}" id="shop-hours-form" data-toggle="validator">
                    @csrf
                    <div class="row">
                        <div class="form-group col-12 mb-3">
                            <span class="fw-bold">{{ __('messages.shop_name') }}:</span>
                            <span class="text-muted ms-1"><strong> {{ ucfirst($shop->translate('shop_name')) }}</strong></span>
                            <input type="hidden" name="branch_id" value="{{ $shop->id }}">
                        </div>
                    </div>

                    @php
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    @endphp

                    @foreach($days as $day)
                    @php
                        $dayData = $shopHoursByDay[$day] ?? null;
                        $startVal = $dayData ? \Carbon\Carbon::parse($dayData->start_time)->format('H:i') : '09:00';
                        $endVal = $dayData ? \Carbon\Carbon::parse($dayData->end_time)->format('H:i') : '18:00';
                        $isDayOff = $dayData ? (bool) $dayData->is_holiday : ($day === 'sunday');
                        $savedBreaks = $dayData && is_array($dayData->breaks) ? $dayData->breaks : [];
                    @endphp
                    <div class="border rounded p-3 mb-4 shop-hour-day-bg day-block shop-hours" data-day="{{ $day }}">
                        <h6 class="text-primary text-capitalize mb-3">{{ $day }}</h6>
                        <div class="row">
                            <div class="form-group col-md-3 mb-3">
                                <input type="text" class="form-control shop-hour-flatpickr day-start {{ $errors->has("hours.{$day}.start") ? 'is-invalid' : '' }}" name="hours[{{ $day }}][start]" value="{{ old("hours.{$day}.start", $startVal) }}" placeholder="9:00 AM">
                                @error("hours.{$day}.start")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-3 mb-3">
                                <input type="text" class="form-control shop-hour-flatpickr day-end {{ $errors->has("hours.{$day}.end") ? 'is-invalid' : '' }}" name="hours[{{ $day }}][end]" value="{{ old("hours.{$day}.end", $endVal) }}" placeholder="6:00 PM">
                                @error("hours.{$day}.end")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-3 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input day-off-checkbox" type="checkbox" name="hours[{{ $day }}][day_off]" value="1" id="day_off_{{ $day }}" data-day="{{ $day }}" {{ $isDayOff ? 'checked' : '' }}>
                                    <label class="form-check-label" for="day_off_{{ $day }}">{{ __('messages.add_day_off') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="breaks-section" data-day="{{ $day }}" style="{{ count($savedBreaks) > 0 ? '' : 'display: none;' }}">
                            <h6 class="form-label mb-2">{{ __('messages.break') }}</h6>
                            <div class="breaks-wrapper" data-day="{{ $day }}" data-initial-break-count="{{ count($savedBreaks) }}">
                                @foreach($savedBreaks as $idx => $b)
                                @php
                                    $bs = isset($b['start_break']) ? \Carbon\Carbon::parse($b['start_break'])->format('H:i') : '12:00';
                                    $be = isset($b['end_break']) ? \Carbon\Carbon::parse($b['end_break'])->format('H:i') : '13:00';
                                @endphp
                                <div class="break-item row mb-2">
                                    <div class="col-md-3 form-group"><input type="text" class="form-control shop-hour-flatpickr" name="hours[{{ $day }}][breaks][{{ $idx }}][start]" value="{{ $bs }}" placeholder="12:00 PM"></div>
                                    <div class="col-md-3 form-group"><input type="text" class="form-control shop-hour-flatpickr" name="hours[{{ $day }}][breaks][{{ $idx }}][end]" value="{{ $be }}" placeholder="1:00 PM"></div>
                                    <div class="col-md-2 form-group d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger remove-break">{{ __('messages.remove') }}</button></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="add-break-btn-wrap" data-day="{{ $day }}">
                            <button type="button" class="btn btn-link p-0 border-0 align-baseline add-break-btn fw-bold text-decoration-none" data-day="{{ $day }}">
                                <span class="add-break-icon rounded-circle d-inline-flex align-items-center justify-content-center me-1 bg-primary text-white"><i class="fa fa-plus"></i></span>{{ __('messages.add_break') }}
                            </button>
                        </div>
                    </div>
                    @endforeach

                    <div class="mt-3">
                        <button type="submit" class="btn btn-md btn-primary float-end">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Translation strings
            var translations = {
                remove: "{{ __('messages.remove') }}"
            };
            
            var breakIndex = {};
            function isDarkMode() {
                return document.documentElement.getAttribute('data-bs-theme') === 'dark' || document.body.classList.contains('dark');
            }
            var flatpickrConfig = {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: false,
                altInput: true,
                altFormat: 'h:i K',
                defaultHour: 9,
                defaultMinute: 0,
                onReady: function(selectedDates, dateStr, instance) {
                    if (!instance.calendarContainer) return;
                    if (isDarkMode()) instance.calendarContainer.classList.add('flatpickr-dark-mode');
                    else instance.calendarContainer.classList.remove('flatpickr-dark-mode');
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    if (!instance.calendarContainer) return;
                    if (isDarkMode()) instance.calendarContainer.classList.add('flatpickr-dark-mode');
                    else instance.calendarContainer.classList.remove('flatpickr-dark-mode');
                }
            };
            function initShopHourFlatpickr(el) {
                if (typeof flatpickr === 'undefined') return;
                var nodes = [];
                if (!el) nodes = document.querySelectorAll('.shop-hour-flatpickr');
                else if (el.nodeType) nodes = [el];
                else if (typeof el === 'string') nodes = document.querySelectorAll(el);
                else nodes = Array.prototype.slice.call(el);
                nodes.forEach(function(input) {
                    if (input._flatpickr) return;
                    flatpickr(input, flatpickrConfig);
                });
            }
            initShopHourFlatpickr('.shop-hour-flatpickr');

            document.querySelectorAll('.add-break-btn').forEach(function(btn) {
                var day = btn.getAttribute('data-day');
                var wrap = document.querySelector('.breaks-wrapper[data-day="' + day + '"]');
                breakIndex[day] = (wrap && wrap.getAttribute('data-initial-break-count')) ? parseInt(wrap.getAttribute('data-initial-break-count'), 10) : 0;
                btn.addEventListener('click', function() {
                    var section = document.querySelector('.breaks-section[data-day="' + day + '"]');
                    var wrapper = document.querySelector('.breaks-wrapper[data-day="' + day + '"]');
                    if (!section || !wrapper) return;
                    var idx = breakIndex[day]++;
                    var html = '<div class="break-item row mb-2">' +
                        '<div class="col-md-3 form-group"><input type="text" class="form-control shop-hour-flatpickr" name="hours[' + day + '][breaks][' + idx + '][start]" value="12:00" placeholder="12:00 PM"></div>' +
                        '<div class="col-md-3 form-group"><input type="text" class="form-control shop-hour-flatpickr" name="hours[' + day + '][breaks][' + idx + '][end]" value="13:00" placeholder="1:00 PM"></div>' +
                        '<div class="col-md-2 form-group d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger remove-break">' + translations.remove + '</button></div></div>';
                    wrapper.insertAdjacentHTML('beforeend', html);
                    section.style.display = '';
                    initShopHourFlatpickr(wrapper.querySelectorAll('.break-item:last-child .shop-hour-flatpickr'));
                });
            });
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-break')) {
                    var item = e.target.closest('.break-item');
                    var wrapper = item && item.closest('.breaks-wrapper');
                    item.remove();
                    if (wrapper && wrapper.querySelectorAll('.break-item').length === 0) {
                        var section = wrapper.closest('.breaks-section');
                        if (section) section.style.display = 'none';
                    }
                }
            });

            function setDayOffState(dayBlock, isDayOff) {
                var startIn = dayBlock.querySelector('.day-start');
                var endIn = dayBlock.querySelector('.day-end');
                var addBreakWrap = dayBlock.querySelector('.add-break-btn-wrap');
                var breaksSection = dayBlock.querySelector('.breaks-section');
                [startIn, endIn].forEach(function(inp) {
                    if (!inp) return;
                    inp.disabled = isDayOff;
                    inp.readOnly = isDayOff;
                    if (inp._flatpickr) {
                        inp._flatpickr.close();
                        if (inp._flatpickr.altInput) {
                            inp._flatpickr.altInput.disabled = isDayOff;
                            inp._flatpickr.altInput.readOnly = isDayOff;
                        }
                    }
                });
                if (addBreakWrap) addBreakWrap.style.display = isDayOff ? 'none' : '';
                if (breaksSection) breaksSection.style.display = isDayOff ? 'none' : (breaksSection.querySelectorAll('.break-item').length > 0 ? '' : 'none');
            }
            document.querySelectorAll('.day-off-checkbox').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var dayBlock = this.closest('.day-block');
                    if (dayBlock) setDayOffState(dayBlock, this.checked);
                });
            });
            document.querySelectorAll('.day-block').forEach(function(block) {
                var cb = block.querySelector('.day-off-checkbox');
                if (cb && cb.checked) setDayOffState(block, true);
            });
            if (document.querySelector('.select2js') && typeof $ !== 'undefined' && $.fn.select2) {
                $('.select2js').select2({ width: '100%' });
            }
        });
    </script>
</x-master-layout>

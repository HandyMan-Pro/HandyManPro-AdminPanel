<!DOCTYPE html>
<html onload="pageLoad()" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' , }}">
<!-- <html lang="en" > -->
<head>
    @yield('before_head')
    @include('landing-page.partials._head')


    @yield('after_head')


</head>
<script>
    var frontendLocale = "{{ session()->get('locale') ?? 'en' }}";
    window.frontendLocale = frontendLocale;
    sessionStorage.setItem("local", frontendLocale);
    (function() {
        const savedTheme = localStorage.getItem('data-bs-theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.toggle('dark', savedTheme === 'dark');
        });
    })();
</script>

{{-- <script>
document.addEventListener("DOMContentLoaded", function () {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            fetch("{{ route('user.set-location') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                })
            });
        });
    }
});
</script> --}}

<body class="body-bg">


    <span class="screen-darken"></span>

    <div id="loading">
        @include('landing-page.partials.loading')
    </div>


    <main class="main-content" id="landing-app">
        <div class="position-relative">

            @include('landing-page.partials._header')
        </div>
        @yield('content')
    </main>

    @include('landing-page.partials._footer')

    @include('landing-page.partials.cookie')

    @include('landing-page.partials.back-to-top')



  @yield('before_script')
    @include('landing-page.partials._scripts')
    @include('landing-page.partials._currencyscripts')
    @yield('after_script')

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
    function readMoreBtn() {
        var readMoreBtns = document.querySelectorAll(".readmore-btn");

        // Get translations from Laravel
        var readMoreText = @json(__('landingpage.read_more'));
        var readLessText = @json(__('landingpage.read_less'));

        readMoreBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var container = btn.previousElementSibling; // Assuming the <p> is the previous sibling
                if (container.classList.contains('active')) {
                    container.classList.remove('active');
                    btn.innerHTML = readMoreText;
                } else {
                    container.classList.add("active");
                    btn.innerHTML = readLessText;
                }
            });
        });
    }
    readMoreBtn();
</script>


    <script>
        function pageLoad() {
            var theme = localStorage.getItem('data-bs-theme');
            if (theme == null) {
                theme = 'light';
            }
            $('html').attr('data-bs-theme', theme);

            if (theme == 'dark') {
                jQuery('body').addClass('dark');
                $('.darkmode-logo').addClass('d-none')
                $('.light-logo').removeClass('d-none')
            } else {
                jQuery('body').removeClass('dark');
                $('.darkmode-logo').removeClass('d-none')
                $('.light-logo').addClass('d-none')
            }
        }
        pageLoad();

        const savedTheme = localStorage.getItem('data-bs-theme');
        if (savedTheme === 'dark') {
            $('html').attr('data-bs-theme', 'dark');
        } else {
            $('html').attr('data-bs-theme', 'light');
        }

        $('.change-mode').on('click', function() {
            const body = jQuery('body')
            var html = $('html').attr('data-bs-theme');
            console.log('mode ' +html);

            if (html == 'light') {
                body.addClass('dark');
                $('html').attr('data-bs-theme', 'dark');
                $('.darkmode-logo').addClass('d-none')
                $('.light-logo').removeClass('d-none')
                localStorage.setItem('dark', true)
                localStorage.setItem('data-bs-theme', 'dark')
            } else {

                body.removeClass('dark');
                $('html').attr('data-bs-theme', 'light');
                $('.darkmode-logo').removeClass('d-none')
                $('.light-logo').addClass('d-none')
                localStorage.setItem('dark', false)
                localStorage.setItem('data-bs-theme', 'light')
            }

        })

    </script>

    <script>
        $(document).ready(function() {
            $('.textbuttoni').click(function() {
                $(this).prev('.custome-seatei').toggleClass('active');
                if ($(this).text() === '{{ __('landingpage.read_more') }}') {
                    $(this).text('{{ __('landingpage.read_less') }}');
                } else {
                    $(this).text('{{ __('landingpage.read_more') }}');
                }
            });
        });
    </script>

    {{-- Wishlist handlers for service cards (DataTable AJAX content - scripts in injected HTML do not execute) --}}
    <script>
        $(document).ready(function() {
            var baseUrl = document.querySelector('meta[name="baseUrl"]') ? document.querySelector('meta[name="baseUrl"]').getAttribute('content') : '';
            if (!baseUrl) return;

            $(document).on('click', '.service-box-card .save_fav', function() {
                var form = $(this).closest('form');
                var btn = $(this);
                var serviceId = form.find('.service_id').data('service-id');
                var userId = form.find('input[name="user_id"]').val();

                $.ajax({
                    url: baseUrl + '/api/save-favourite',
                    type: 'POST',
                    data: {
                        _token: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '',
                        service_id: serviceId,
                        user_id: userId,
                    },
                    success: function(response) {
                        btn.removeClass('save_fav').addClass('delete_fav');
                        btn.find('svg').attr('fill', 'currentColor');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ title: 'Done', text: response.message, icon: 'success', iconColor: '#5F60B9' });
                        }
                    },
                    error: function(xhr) { console.error('Error:', xhr); }
                });
            });

            $(document).on('click', '.service-box-card .delete_fav', function() {
                var form = $(this).closest('form');
                var btn = $(this);
                var $card = btn.closest('.service-box-card');
                var serviceId = form.find('.service_id').data('service-id') || form.find('input[name="service_id"]').val() || $card.data('service-id');
                var userId = form.find('input[name="user_id"]').val();
                var isWishlistPage = window.location.pathname.indexOf('favourite-service') !== -1;

                // Disable button during API call for smoother UX
                btn.prop('disabled', true).addClass('opacity-50');

                if (!serviceId) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: 'Error', text: 'Invalid request. Please refresh the page.', icon: 'error' });
                    }
                    btn.prop('disabled', false).removeClass('opacity-50');
                    return;
                }

                $.ajax({
                    url: baseUrl + '/api/delete-favourite',
                    type: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    xhrFields: { withCredentials: true },
                    data: {
                        _token: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '',
                        service_id: serviceId,
                        user_id: userId,
                    },
                    success: function(response) {
                        if (isWishlistPage) {
                            // Find table from clicked card (reliable when inside Vue component)
                            var $table = $card.closest('table');
                            if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table[0])) {
                                $table.DataTable().ajax.reload(null, false);
                            } else {
                                var $row = $card.closest('tr');
                                var $tbody = $row.closest('tbody');
                                if ($row.length) {
                                    $row.fadeOut(300, function() {
                                        $(this).remove();
                                        if ($tbody.length && $tbody.find('tr').length === 0) {
                                            var msg = (window.i18n && window.i18n.global && window.i18n.global.t) ? window.i18n.global.t('landingpage.no_data_available_in_table') : 'No data available in the table';
                                            $tbody.append('<tr class="odd"><td colspan="1" class="dataTables_empty text-center py-5">' + msg + '</td></tr>');
                                        }
                                    });
                                }
                            }
                        } else {
                            // On other pages, toggle button to save_fav state
                            btn.removeClass('delete_fav').addClass('save_fav');
                            btn.find('svg').attr('fill', 'none');
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ title: 'Done', text: response.message, icon: 'success', iconColor: '#5F60B9' });
                        }
                    },
                    error: function(xhr) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to remove from wishlist.', icon: 'error' });
                        }
                        console.error('Error:', xhr);
                    },
                    complete: function() {
                        btn.prop('disabled', false).removeClass('opacity-50');
                    }
                });
            });

            $(document).on('click', '.service-box-card .service-heading, .service-box-card .service-img', function(e) {
                e.preventDefault();
                var serviceId = $(this).closest('.service-box-card').data('service-id');
                var href = $(this).attr('href') || $(this).closest('a').attr('href');
                if (!href) return;

                var storedServiceIds = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
                if (!storedServiceIds.includes(serviceId)) {
                    storedServiceIds.unshift(serviceId);
                    storedServiceIds = storedServiceIds.slice(0, 10);
                    localStorage.setItem('recentlyViewed', JSON.stringify(storedServiceIds));
                }
                $.ajax({
                    url: baseUrl + '/save-recently-viewed/' + serviceId,
                    type: 'POST',
                    data: { _token: document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '' },
                    error: function() { console.error('Error storing recently viewed'); }
                });
                window.location.href = href;
            });
        });
    </script>

</body>
</html>

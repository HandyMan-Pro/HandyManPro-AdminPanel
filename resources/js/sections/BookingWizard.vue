<template>

    <div class="row">
        <div class="col-12" v-if="!service.package_type">
            <div class="bg-light p-sm-5 p-3 mb-5 booking-detail-service-box rounded-4">
              <div class="row align-items-center">
                <div class="col-lg-3 col-md-4">
                  <div class="img flex-shrink-0">
                    <img :src="service.service_image" class="object-cover rounded-3 w-100 img-fluid book-service-img" alt="service">
                  </div>
                </div>
                <div class="col-lg-9 col-md-8 mt-md-0 mt-3">
                  <div class="content flex-grow-1">
                  <div class="d-sm-flex align-items-center gap-3 justify-content-between">
                    <h4 class="mb-0">{{ service.name }}</h4>
                   <div class="flex-shrink-0 d-inline-flex align-items-center gap-2 mt-sm-0 mt-2">
  <span class="text-primary fw-500 d-inline-block position-relative h5">
    <span v-if="service.price > 0">{{formatCurrencyVue(service.price*quantity)}}</span>
    <span v-else>Free</span>
  </span>

  <template v-if="formattedDuration(service.duration)">
    <span class="font-size-18">/</span>
    <span class="h5 text-body">{{ formattedDuration(service.duration) }}</span>
  </template>
</div>
                  </div>
                  <div class="d-sm-flex gap-2 mt-3">
                    <h6 class="m-0 lh-1">{{ $t('messages.category') }}:</h6>
                    <ul class="list-inline mt-sm-0 mt-2 mb-0 p-0 d-flex align-items-center flex-wrap category-list lh-1">
                      <li>{{ service.category_name }}</li>
                      <li v-if="service.subcategory_name">{{ service.subcategory_name }}</li>
                    </ul>
                  </div>
                  <div class="d-flex align-items-center flex-wrap gap-2 mt-4">
                    <div class="d-inline-flex align-items-center gap-2 felx-shrink-0">
                      <div class="flex-shrink-0">
                        <img :src="service.provider_image" alt="service" class="img-fluid rounded-3 object-cover avatar-24">
                      </div>
                      <a :href="`${baseUrl}/provider-detail/${service.provider_id}`">
                          <span class="font-size-14 service-user-name">{{ service.provider_name }}</span>
                      </a>
                    </div>
                    <div>/</div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                      <span class="text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" class="service-rating">
                          <path d="M6.58578 0.85525L7.92167 3.44562C8.02009 3.63329 8.20793 3.76362 8.42458 3.79259L11.4252 4.21427C11.6005 4.23802 11.7595 4.32723 11.8669 4.46335C11.9731 4.59773 12.0187 4.76803 11.9929 4.93543C11.9719 5.07445 11.9041 5.20304 11.8003 5.30151L9.62603 7.33523C9.467 7.47714 9.39498 7.68741 9.43339 7.89304L9.96871 10.7522C10.0257 11.0974 9.78867 11.4229 9.43339 11.4884C9.28696 11.511 9.13693 11.4872 9.0049 11.4224L6.32833 10.0768C6.12968 9.98005 5.89503 9.98005 5.69639 10.0768L3.01982 11.4224C2.69094 11.5909 2.28346 11.4762 2.10042 11.1634C2.0326 11.0389 2.0086 10.897 2.0308 10.7585L2.56612 7.89883C2.60453 7.69378 2.53191 7.48236 2.37348 7.34044L0.19921 5.30788C-0.0594455 5.06692 -0.0672472 4.67014 0.181806 4.42048C0.187207 4.41527 0.193209 4.40948 0.19921 4.40369C0.302432 4.30232 0.438061 4.23802 0.584493 4.22123L3.58514 3.79896C3.80118 3.76942 3.98902 3.64025 4.08805 3.45141L5.37592 0.85525C5.49055 0.632821 5.7282 0.494383 5.98625 0.500175H6.06667C6.29052 0.526241 6.48556 0.660046 6.58578 0.85525Z" fill="currentColor"></path>
                        </svg>
                      </span>
                      <h6 class="font-size-14">{{ formatRating(service.total_rating) }}<span class="text-body"> ({{ service.total_reviews }} {{ $t('messages.reviews') }})</span></h6>
                    </div>
                  </div>
                </div>
                </div>
              </div>
            </div>
        </div>
        <div class="col-12" v-else>
            <div class="bg-light p-sm-5 p-3 mb-5 booking-detail-service-box rounded-4">
              <div class="row align-items-center">
                <div class="col-lg-3 col-md-4">
                  <div class="img flex-shrink-0">
                    <img :src="service.package_image" class="object-cover rounded-3 w-100 img-fluid" alt="service">
                  </div>
                </div>
                <div class="col-lg-9 col-md-8 mt-md-0 mt-3">
                  <div class="content flex-grow-1">
                  <div class="d-sm-flex align-items-center gap-3 justify-content-between">
                    <!-- <h4 class="mb-0">{{ service.name }}</h4>
                    <p>{{service.description}}</p> -->

                      <div class="d-inline-flex align-items-sm-center align-items-start flex-sm-row flex-column gap-3">
                        <div class="comment-user-info">
                            <h4 class="mb-0">{{ service.name }}</h4>
                            <span class="">
                              {{service.description}}
                            </span>
                            <div class="mt-4 " v-if="service.end_at !== null">
                              <div class="d-inline-flex">
                                <span>{{$t('messages.Package_Expire')}}: </span>&nbsp;
                                <p class="text-primary commnet-content m-0">
                                  {{ formatDate(service.end_at) }}
                                </p>
                              </div>
                            </div>
                        </div>
                      </div>

                    <div class="flex-shrink-0 d-inline-flex align-items-center gap-2 mt-sm-0 mt-2">
                      <span class="text-primary fw-500 d-inline-block position-relative font-size-18">{{ formatCurrencyVue(service.price) }} (<del>{{ formatCurrencyVue(service.total_price) }}</del>)</span>
                    </div>
                  </div>
                </div>
                </div>
              </div>
            </div>
        </div>
        <div class="col-12" v-if="selectedShop">
            <h5 class="mb-3">About Shop</h5>
            <div class="p-4 border rounded-3 about-provider-box">
                <div class="row g-4 align-items-start">
                    <div class="col-md-3">
                        <div class="img flex-shrink-0">
                            <a :href="shopDetailUrl(selectedShop.id)"><img :src="selectedShop.shop_image" :alt="selectedShop.shop_name" class="object-cover rounded-3 w-100 h-150px img-fluid book-service-img" style="height: 230px;" ></a>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-12">
                                <h5 class="mb-1"><a :href="shopDetailUrl(selectedShop.id)">{{ selectedShop.shop_name }}</a></h5>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="bg-light p-3 rounded h-100">
                                    <p class="mb-0"><i class="fas fa-phone me-2 text-primary"></i>{{ selectedShop.contact_number }}</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="bg-light p-3 rounded h-100">
                                    <p class="mb-0 text-break"><i class="fas fa-envelope me-2 text-primary"></i>{{ selectedShop.email }}</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="bg-light p-3 rounded h-100">
                                   <p class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>{{ moment(selectedShop.shop_start_time).format("h:mm A") }} - {{ moment(selectedShop.shop_end_time).format("h:mm A") }}</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="bg-light p-3 rounded h-100">
                                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>{{ selectedShop.address }}, {{ selectedShop.city?.name }}, {{ selectedShop.state?.name }}, {{ selectedShop.country?.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 mt-5">
          <form  @submit="formSubmit">
           <!-- <input type="hidden" name="_token" :value="csrfToken"> -->
            <div class="col-12">

                <template v-if="!isChildComponentVisible">

                  <div class="row">
                    <div :class="service.price == 0 ? 'col-lg-12' : 'col-lg-7'">
                      <div  class="booking-list-content-active">
                          <h5 class="text-capitalize">{{ $t('messages.schedule_ervice') }}</h5>


                          <div v-if="serviceaddon && serviceaddon.length">
                            <h5 class="mt-5 mb-3">{{ $t('landingpage.Add-ons') }}</h5>
                            <div v-for="(service, index) in serviceaddon" :key="index" class="mb-4 pb-4 d-flex align-items-sm-center aling-items-start flex-sm-row flex-column gap-5">
                                <div class="flex-shrink-0 provider-image-container">
                                    <img :src="service.serviceaddon_image" alt="service-image"
                                        class="img-fluid w-100" style="width: 100px; height:100px;">
                                </div>
                                <div>
                                    <h5 class="text-capitalize mb-1">{{ service.name }}</h5>
                                    <h6>{{ formatCurrencyVue(service.price) }}</h6>
                                </div>
                                <div>
                                  <span v-if="!service.removed" class="d-block btn btn-light text-danger" @click="toggleAddon(index, true)" >{{ $t('landingpage.remove') }}</span>
                                  <span v-else class="d-block btn btn-light text-primary" @click="toggleAddon(index, false)" >{{ $t('landingpage.add') }}</span>
                                </div>
                            </div>
                          </div>

                          <div class="mt-5 card bg-light rounded-3">
                            <div class="card-body booking-service-form">
                              <div class="row g-3">
                                <div class="mt-3" v-if="props.shop_list?.length">
                                    <div class="form-group">
                                    <label for="shop_id" class="form-label">Select Shop</label>
                                        <select
                                            id="shop_id"
                                            v-model="selectedShopId"
                                            class="form-select select2 bg-white"
                                        >
                                            <!-- Placeholder (will only show if no shops) -->
                                            <option disabled value="">
                                            {{ $t("messages.select_shop") }}
                                            </option>

                                            <!-- Shops list -->
                                            <option
                                            v-for="shop in props.shop_list"
                                            :key="shop.id"
                                            :value="shop.id"
                                            >
                                            {{ shop.shop_name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                  <div v-if="service.is_slot == 1" class="col-12">

                                      <div class="mt-1">

                                          <div>

                                              <div class="px-4 pt-3 pb-4 bg-body">
                                                  <div class="select-week-days">

                                                      <div class="custom-form-field">
                                                          <label class="form-label">{{ $t('landingpage.date_time') }}</label>


                                                          <DatePicker v-model="DateFormate" view="weekly" :attributes="todos" mode="DateFormate" :min-date="new Date()"  @click="handleDateSelect(DateFormate)" expanded/>

                                                          <span v-if="errorMessages['date']">
                                                              <ul class="text-danger">
                                                                <li v-for="err in errorMessages['date']" :key="err">{{ err }}</li>
                                                              </ul>
                                                            </span>
                                                            <span class="text-danger">{{ errors.date }}</span>
                                                        </div>

                                                  </div>
                                                  <div v-if="date==null" class="time-slot mt-3 pt-3 border-top">
                                                      <p class="text-capitalize mb-2 lh-1">{{ $t('landingpage.date_time') }}</p>

                                                          <div v-if="availableSlotsForDate.length > 0">
                                                              <ul class="list-inline m-0 d-flex align-items-center flex-wrap gap-3">
                                                                  <li class="time-slot" v-for="timeSlot in availableSlotsForDate" :key="timeSlot">
                                                                      <input type="radio" :id="timeSlot" v-model="start_time" :value="timeSlot" name="start_time" class="btn-check"/>
                                                                      <label :for="timeSlot" class="btn d-block py-2 px-2 time-slot-btn">
                                                                          {{ timeSlot }}
                                                                      </label>
                                                                  </li>
                                                              </ul>
                                                          </div>

                                                          <div v-else>
                                                              {{ $t('landingpage.slot_not_available') }}
                                                          </div>

                                                          <span v-if="errorMessages['start_time']">
                                                              <ul class="text-danger">
                                                                <li v-for="err in errorMessages['start_time']" :key="err">{{ err }}</li>
                                                              </ul>
                                                            </span>
                                                            <span class="text-danger">{{ errors.start_time }}</span>

                                                  </div>
                                              </div>
                                          </div>

                                  </div>

                                  </div>

                                  <div v-else class="col-sm-6">
                                      <label class="form-label">{{ $t('landingpage.date_time') }}</label>
                                      <div class="input-group icon-left custom-form-field flex-nowrap">
                                          <span class="input-group-text flex-shrink-0" id="dateandtime">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
                                                  <path d="M1.32031 6.5531H14.6883" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M11.3322 9.4823H11.3392" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M8.00408 9.4823H8.01103" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M4.66815 9.4823H4.67509" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M11.3322 12.3971H11.3392" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M8.00408 12.3971H8.01103" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M4.66815 12.3971H4.67509" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M11.0329 1V3.46809" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path d="M4.97435 1V3.46809" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                  <path fill-rule="evenodd" clip-rule="evenodd" d="M11.1787 2.18433H4.82822C2.6257 2.18433 1.25 3.41128 1.25 5.6666V12.4538C1.25 14.7446 2.6257 15.9999 4.82822 15.9999H11.1718C13.3812 15.9999 14.75 14.7659 14.75 12.5106V5.6666C14.7569 3.41128 13.3882 2.18433 11.1787 2.18433Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                              </svg>
                                          </span>
                                          <flat-pickr
                                                v-model="date"
                                                :config="config"
                                                class="form-control"
                                                placeholder="Select date and time"
                                                name="date" />
                                      </div>

                                      <span v-if="errorMessages['date']">
                                          <ul class="text-danger">
                                            <li v-for="err in errorMessages['date']" :key="err">{{ err }}</li>
                                          </ul>
                                        </span>
                                        <span class="text-danger">{{ errors.date }}</span>
                                  </div>



                                  <div v-if="service.type=='fixed'" class="col-sm-4" :class="{'mt-4': service.is_slot == 1}">
                                      <label class="form-label">{{ $t('landingpage.quantity') }}</label>
                                      <div class="custom-form-field">
                                          <div class="btn-group iq-qty-btn" data-qty="btn" role="group">
                                              <button type="button" class="iq-quantity-plus" @click="decrement()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128Z"/></svg>
                                              </button>
                                              <input type="text" class="input-display" id="quntity" v-model="quantity" name="quantity" disabled />
                                              <button type="button" class="iq-quantity-minus"  @click="increment()">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z"/></svg>
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                                  <div class="col-12 mt-5">
                                      <label class="form-label">{{ $t('landingpage.location') }}</label>
                                      <div class="input-group icon-left custom-form-field">
                                          <span class="input-group-text align-items-start pt-4">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15"
                                                  viewBox="0 0 14 15" fill="none">
                                                  <path fill-rule="evenodd" clip-rule="evenodd"
                                                      d="M8.875 6.37538C8.875 5.33943 8.03557 4.5 7.00038 4.5C5.96443 4.5 5.125 5.33943 5.125 6.37538C5.125 7.41057 5.96443 8.25 7.00038 8.25C8.03557 8.25 8.875 7.41057 8.875 6.37538Z"
                                                      stroke="currentColor" stroke-width="1.5"
                                                      stroke-linecap="round" stroke-linejoin="round" />
                                                  <path fill-rule="evenodd" clip-rule="evenodd"
                                                      d="M6.99963 14.25C6.10078 14.25 1.375 10.4238 1.375 6.42247C1.375 3.28998 3.89283 0.75 6.99963 0.75C10.1064 0.75 12.625 3.28998 12.625 6.42247C12.625 10.4238 7.89849 14.25 6.99963 14.25Z"
                                                      stroke="currentColor" stroke-width="1.5"
                                                      stroke-linecap="round" stroke-linejoin="round" />
                                              </svg>
                                          </span>
                                          <textarea class="form-control"
                                          :placeholder="$t('placeholder.address')" v-model="address" name="address"></textarea>


                                      </div>
                                      <span v-if="errorMessages['address']">
                                          <ul class="text-danger">
                                            <li v-for="err in errorMessages['address']" :key="err">{{ err }}</li>
                                          </ul>
                                      </span>
                                      <span class="text-danger">{{ errors.address }}</span>

                                      <div>
                                        <!-- <a @click="getCurrentLocation" class="btn btn-primary mt-5">Get Current Location</a> -->
                                        <a @click="getCurrentLocation" class="btn btn-primary mt-5">
                                          <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                          <span v-else>{{ $t('landingpage.get_current_location') }}</span>
                                        </a>
                                      </div>
                                  </div>
                              </div>
                            </div>
                          </div>
                      </div>
                    </div>
                    <div class="col-lg-5" v-if="service.price>0">
                      <div class="booking-list-content">
                            <div v-if="redeem_points.max_discount > 0" class="mb-4">
                                <div v-if="user_points > 0" class="mt-3 mb-3 border border-muted rounded-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3 bg-primary-subtle p-3">
                                        <p class="mb-0">
                                            <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.75 4.11276C9.2244 2.8902 8.48928 1.37028 7.84272 0.72396C6.87744 -0.24132 5.31 -0.24132 4.34472 0.72396C3.37944 1.68948 3.37944 3.25692 4.34472 4.2222C4.71984 4.59732 5.38944 5.00268 6.12456 5.37948H2.25C1.00728 5.37924 0 6.38676 0 7.62924V17.2499C0 18.4926 1.00728 19.4999 2.25 19.4999H17.25C18.4927 19.4999 19.5 18.4926 19.5 17.2499V7.62924C19.5 6.38676 18.4927 5.37924 17.25 5.37948H13.3754C14.1106 5.00268 14.7802 4.59732 15.1553 4.2222C16.1206 3.25692 16.1206 1.68948 15.1553 0.72396C14.19 -0.24132 12.6226 -0.24132 11.6573 0.72396C11.0107 1.37028 10.2756 2.8902 9.75 4.11276ZM18 15.027V17.2499C18 17.6641 17.6642 17.9999 17.25 17.9999H2.25C1.83576 17.9999 1.5 17.6641 1.5 17.2499V15.027H18ZM7.93944 6.87948H2.25C1.83576 6.87948 1.5 7.21524 1.5 7.62924V13.527H18V7.62924C18 7.21524 17.6642 6.87948 17.25 6.87948H11.5606L13.3738 8.69244C13.6666 8.98524 13.6666 9.46044 13.3738 9.75324C13.0812 10.0458 12.6058 10.0458 12.3132 9.75324L9.75 7.19004L7.1868 9.75324C6.89424 10.0458 6.4188 10.0458 6.12624 9.75324C5.83344 9.46044 5.83344 8.98524 6.12624 8.69244L7.93944 6.87948ZM11.0491 4.83012C11.5006 3.73452 12.1404 2.36196 12.7178 1.78476C13.0978 1.40484 13.7148 1.40484 14.0947 1.78476C14.4746 2.16468 14.4746 2.78148 14.0947 3.16164C13.5173 3.73884 12.145 4.37892 11.0491 4.83012ZM8.45088 4.83012C7.35504 4.37892 5.98272 3.73884 5.40528 3.16164C5.02536 2.78148 5.02536 2.16468 5.40528 1.78476C5.7852 1.40484 6.40224 1.40484 6.78216 1.78476C7.3596 2.36196 7.99944 3.73452 8.45088 4.83012Z" fill="#5F60B9"/>
                                            </svg>
                                            {{ $t('landingpage.redeem_points') }}
                                        </p>
                                        <span v-if="user_points && user_points > 0" class="text-primary border border-primary rounded-pill px-3">
                                            {{ user_points }} {{ $t('landingpage.pts_available') }}
                                        </span>
                                        <span v-else class="text-muted border border-secondary rounded-pill px-3">
                                            0 {{ $t('landingpage.pts_available') }}
                                        </span>
                                    </div>
                                    <div v-if="redeem_points.redeem_type == 'full'" class="p-3">
                                        <div class="border border-primary border-dashed rounded-3 p-3 d-flex justify-content-between align-items-center gap-3">
                                            <svg width="50" height="50" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0.907267 16.2387H1.84338V23.0901C1.84338 23.7579 2.37126 24.2992 3.02238 24.2992H20.3144C20.9656 24.2992 21.4934 23.7579 21.4934 23.0901V16.2387H22.4296C22.7026 16.2391 22.9563 16.0942 23.0997 15.8559C23.243 15.6175 23.2555 15.32 23.1326 15.0699L21.5606 11.8457C21.4278 11.5726 21.1551 11.4007 20.8576 11.4024H2.4793C2.18128 11.4011 1.90859 11.5739 1.77623 11.8477L0.204194 15.0719C0.082241 15.3218 0.0952285 15.6187 0.238459 15.8564C0.381735 16.0941 0.634807 16.2387 0.907267 16.2387ZM2.62939 23.0901V16.2387H9.46052C9.75808 16.2403 10.0307 16.0684 10.1636 15.7954L11.2754 13.5126V23.4931H3.02238C2.80537 23.4931 2.62939 23.3127 2.62939 23.0901ZM20.7075 23.0901C20.7075 23.3127 20.5315 23.4931 20.3145 23.4931H12.0615V13.5127L13.1733 15.793C13.3055 16.067 13.5782 16.24 13.8763 16.2387H20.7075V23.0901ZM20.8576 12.2085L22.4296 15.4327H13.8763L12.3043 12.2085H20.8576ZM2.4793 12.2085H11.0326L9.46057 15.4327H0.907267L2.4793 12.2085Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M16.4554 5.22324C16.7574 4.90784 16.8605 4.44485 16.7218 4.02626C16.5966 3.62396 16.2608 3.32844 15.8541 3.26251L13.7947 2.94494C13.6958 2.92709 13.6121 2.85983 13.5715 2.76561L12.6452 0.763792C12.472 0.372449 12.0916 0.121094 11.6725 0.121094C11.2534 0.121094 10.8731 0.372401 10.6998 0.763792L9.77234 2.76764C9.73177 2.86186 9.64804 2.92912 9.54912 2.94697L7.49097 3.26256C7.08371 3.32882 6.74783 3.62523 6.62321 4.02829C6.48454 4.44688 6.58761 4.90987 6.88968 5.22527L8.3662 6.75957C8.44491 6.84411 8.47977 6.96199 8.4601 7.07714L8.10995 9.25347C8.03447 9.69303 8.21274 10.1379 8.56778 10.396C8.89629 10.6381 9.33132 10.6646 9.68549 10.4641L11.5452 9.42194C11.6255 9.3756 11.7235 9.3756 11.8038 9.42194L13.6635 10.4641C14.0176 10.6646 14.4527 10.6381 14.7812 10.396C15.1353 10.1375 15.3128 9.69294 15.2371 9.25389L14.8869 7.07756C14.8672 6.96227 14.9021 6.84425 14.9808 6.75957L16.4554 5.22324ZM14.4192 6.1905C14.1644 6.45895 14.0495 6.83579 14.1096 7.20532L14.4601 9.38165C14.4866 9.51658 14.4337 9.65492 14.325 9.73549C14.2415 9.79968 14.1289 9.80681 14.0384 9.75363L12.1788 8.71142C11.8636 8.5335 11.4815 8.5335 11.1664 8.71142L9.30668 9.75566C9.21623 9.80898 9.10354 9.80185 9.02018 9.73752C8.91135 9.65695 8.85853 9.51861 8.88501 9.38368L9.23558 7.20735C9.29568 6.83783 9.18068 6.46098 8.9259 6.19253L7.44939 4.65701C7.35589 4.55702 7.3255 4.41146 7.37077 4.28097C7.40292 4.16686 7.49563 4.08147 7.6097 4.06092L9.66826 3.74213C10.0256 3.68422 10.3314 3.44779 10.4841 3.11138L11.4116 1.10753C11.4544 0.997294 11.5584 0.924938 11.6741 0.924938C11.7898 0.924938 11.8939 0.997294 11.9366 1.10753L12.8641 3.11175C13.0163 3.44713 13.3208 3.68328 13.6769 3.74208L15.735 4.06088C15.8491 4.08142 15.9418 4.16681 15.9739 4.28092C16.0193 4.41141 15.9888 4.55697 15.8953 4.65696L14.4192 6.1905Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M2.40171 8.60352L2.22643 9.69169C2.176 9.98739 2.29588 10.2864 2.53453 10.4602C2.7602 10.626 3.05873 10.6439 3.30167 10.5062L4.20163 10.0028L5.10158 10.507C5.34448 10.645 5.64314 10.6271 5.86872 10.4611C6.10737 10.2872 6.22725 9.98819 6.17682 9.69249L5.99449 8.61925L6.73294 7.85352C6.9371 7.64207 7.00696 7.33031 6.91333 7.04868C6.82772 6.77607 6.59975 6.57606 6.32383 6.53161L5.32246 6.37646L4.86698 5.39348C4.74807 5.12753 4.48892 4.95703 4.20361 4.95703C3.9183 4.95703 3.65915 5.12753 3.54023 5.39348L3.08277 6.37646L2.0814 6.53161C1.80475 6.57539 1.57586 6.77551 1.48992 7.04868C1.3968 7.32984 1.46629 7.64084 1.66953 7.85229L2.40171 8.60352ZM3.22701 7.16916C3.47051 7.13062 3.67927 6.97009 3.78349 6.74117L4.20163 5.83918L4.61897 6.74117C4.72324 6.97009 4.93196 7.13067 5.17545 7.16916L6.14182 7.31907L5.43442 8.05377C5.26305 8.23395 5.18559 8.487 5.22575 8.73529L5.40808 9.76059L4.5486 9.27696C4.33228 9.15502 4.07014 9.15502 3.85377 9.27696L3.01355 9.75011L3.17704 8.73491C3.21702 8.48644 3.13942 8.23329 2.96796 8.05301L2.25318 7.3203L3.22701 7.16916Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M19.8005 5.39348C19.6816 5.12753 19.4225 4.95703 19.1371 4.95703C18.8518 4.95703 18.5927 5.12753 18.4738 5.39348L18.0183 6.37646L17.0169 6.53161C16.741 6.5761 16.513 6.77607 16.4274 7.04868C16.3343 7.32984 16.4038 7.64084 16.607 7.85229L17.3384 8.60352L17.1631 9.69169C17.1127 9.98739 17.2326 10.2864 17.4712 10.4602C17.6969 10.626 17.9954 10.6439 18.2384 10.5062L19.1372 10.0028L20.0371 10.507C20.28 10.645 20.5787 10.6271 20.8043 10.4611C21.0429 10.2872 21.1628 9.98819 21.1124 9.69249L20.9301 8.61925L21.6685 7.85352C21.8727 7.64207 21.9425 7.33031 21.8489 7.04868C21.7633 6.77607 21.5353 6.57606 21.2594 6.53161L20.258 6.37646L19.8005 5.39348ZM20.3704 8.05344C20.199 8.23362 20.1215 8.48667 20.1617 8.73496L20.344 9.76026L19.4845 9.27663C19.2682 9.15469 19.0061 9.15469 18.7897 9.27663L17.9491 9.74775L18.1126 8.73255C18.1525 8.48408 18.0749 8.23093 17.9035 8.05065L17.1886 7.31794L18.1633 7.1668C18.4068 7.12826 18.6155 6.96773 18.7197 6.73881L19.1371 5.83928L19.5544 6.74126C19.6587 6.97019 19.8674 7.13076 20.1109 7.16926L21.0773 7.31916L20.3704 8.05344Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M8.92101 1.73344C9.05757 1.73419 9.18477 1.66222 9.25689 1.54329C9.32901 1.42437 9.33578 1.27555 9.27471 1.1503L8.88173 0.344233C8.78469 0.145019 8.54857 0.0641625 8.35431 0.163675C8.16005 0.263187 8.0812 0.505332 8.17824 0.704546L8.57123 1.51061C8.6375 1.64658 8.77276 1.73273 8.92101 1.73344Z" fill="#5F60B9"/>
                                                <path d="M7.06581 2.41805C7.22 2.57079 7.46515 2.56862 7.61672 2.41314C7.76833 2.25766 7.77045 2.00631 7.62151 1.84818L7.22852 1.44517C7.07433 1.29243 6.82918 1.29461 6.67762 1.45008C6.52601 1.60556 6.52389 1.85692 6.67283 2.01504L7.06581 2.41805Z" fill="#5F60B9"/>
                                                <path d="M14.4225 1.73133C14.5722 1.73218 14.7093 1.64575 14.7762 1.50846L15.1692 0.702393C15.2662 0.502046 15.1865 0.259003 14.9911 0.159538C14.7958 0.060073 14.5588 0.14178 14.4618 0.342127L14.0688 1.14819C14.0077 1.27349 14.0145 1.42226 14.0866 1.54119C14.1588 1.66011 14.286 1.73209 14.4225 1.73133Z" fill="#5F60B9"/>
                                                <path d="M16.2694 2.42015L16.6624 2.01714C16.7646 1.91592 16.8056 1.76611 16.7696 1.62518C16.7336 1.48425 16.6263 1.37425 16.4889 1.33736C16.3516 1.30048 16.2054 1.34251 16.1067 1.44727L15.7137 1.85027C15.6116 1.95149 15.5706 2.1013 15.6066 2.24223C15.6425 2.38316 15.7498 2.49316 15.8872 2.53005C16.0247 2.56693 16.1707 2.52495 16.2694 2.42015Z" fill="#5F60B9"/>
                                            </svg>
                                            <div class="bg-primary-subtle text-primary d-flex justify-content-center py-2 px-3 rounded-3 w-100">{{ props.redeem_points.threshold_points }} {{ $t('landingpage.points') }} = {{ formatCurrencyVue(props.redeem_points.max_discount) }}  {{ $t('landingpage.off') }}</div>
                                            <button class="btn btn-primary px-3" type="button" @click="applyLoyaltyPoints" :disabled="!canApplyFullPoints" v-if="!loyaltyPointsApplied">{{ $t('landingpage.apply') }}</button>
                                            <button class="btn btn-danger px-3" type="button" @click="removeLoyaltyPoints" v-else>{{ $t('landingpage.remove') }}</button>
                                        </div>
                                    </div>
                                    <div v-if="redeem_points.redeem_type == 'partial'" class="p-3">
                                        <div class="border border-primary border-dashed rounded-3 p-3 d-flex justify-content-between align-items-center gap-3">
                                            <svg width="50" height="50" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M0.907267 16.2387H1.84338V23.0901C1.84338 23.7579 2.37126 24.2992 3.02238 24.2992H20.3144C20.9656 24.2992 21.4934 23.7579 21.4934 23.0901V16.2387H22.4296C22.7026 16.2391 22.9563 16.0942 23.0997 15.8559C23.243 15.6175 23.2555 15.32 23.1326 15.0699L21.5606 11.8457C21.4278 11.5726 21.1551 11.4007 20.8576 11.4024H2.4793C2.18128 11.4011 1.90859 11.5739 1.77623 11.8477L0.204194 15.0719C0.082241 15.3218 0.0952285 15.6187 0.238459 15.8564C0.381735 16.0941 0.634807 16.2387 0.907267 16.2387ZM2.62939 23.0901V16.2387H9.46052C9.75808 16.2403 10.0307 16.0684 10.1636 15.7954L11.2754 13.5126V23.4931H3.02238C2.80537 23.4931 2.62939 23.3127 2.62939 23.0901ZM20.7075 23.0901C20.7075 23.3127 20.5315 23.4931 20.3145 23.4931H12.0615V13.5127L13.1733 15.793C13.3055 16.067 13.5782 16.24 13.8763 16.2387H20.7075V23.0901ZM20.8576 12.2085L22.4296 15.4327H13.8763L12.3043 12.2085H20.8576ZM2.4793 12.2085H11.0326L9.46057 15.4327H0.907267L2.4793 12.2085Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M16.4554 5.22324C16.7574 4.90784 16.8605 4.44485 16.7218 4.02626C16.5966 3.62396 16.2608 3.32844 15.8541 3.26251L13.7947 2.94494C13.6958 2.92709 13.6121 2.85983 13.5715 2.76561L12.6452 0.763792C12.472 0.372449 12.0916 0.121094 11.6725 0.121094C11.2534 0.121094 10.8731 0.372401 10.6998 0.763792L9.77234 2.76764C9.73177 2.86186 9.64804 2.92912 9.54912 2.94697L7.49097 3.26256C7.08371 3.32882 6.74783 3.62523 6.62321 4.02829C6.48454 4.44688 6.58761 4.90987 6.88968 5.22527L8.3662 6.75957C8.44491 6.84411 8.47977 6.96199 8.4601 7.07714L8.10995 9.25347C8.03447 9.69303 8.21274 10.1379 8.56778 10.396C8.89629 10.6381 9.33132 10.6646 9.68549 10.4641L11.5452 9.42194C11.6255 9.3756 11.7235 9.3756 11.8038 9.42194L13.6635 10.4641C14.0176 10.6646 14.4527 10.6381 14.7812 10.396C15.1353 10.1375 15.3128 9.69294 15.2371 9.25389L14.8869 7.07756C14.8672 6.96227 14.9021 6.84425 14.9808 6.75957L16.4554 5.22324ZM14.4192 6.1905C14.1644 6.45895 14.0495 6.83579 14.1096 7.20532L14.4601 9.38165C14.4866 9.51658 14.4337 9.65492 14.325 9.73549C14.2415 9.79968 14.1289 9.80681 14.0384 9.75363L12.1788 8.71142C11.8636 8.5335 11.4815 8.5335 11.1664 8.71142L9.30668 9.75566C9.21623 9.80898 9.10354 9.80185 9.02018 9.73752C8.91135 9.65695 8.85853 9.51861 8.88501 9.38368L9.23558 7.20735C9.29568 6.83783 9.18068 6.46098 8.9259 6.19253L7.44939 4.65701C7.35589 4.55702 7.3255 4.41146 7.37077 4.28097C7.40292 4.16686 7.49563 4.08147 7.6097 4.06092L9.66826 3.74213C10.0256 3.68422 10.3314 3.44779 10.4841 3.11138L11.4116 1.10753C11.4544 0.997294 11.5584 0.924938 11.6741 0.924938C11.7898 0.924938 11.8939 0.997294 11.9366 1.10753L12.8641 3.11175C13.0163 3.44713 13.3208 3.68328 13.6769 3.74208L15.735 4.06088C15.8491 4.08142 15.9418 4.16681 15.9739 4.28092C16.0193 4.41141 15.9888 4.55697 15.8953 4.65696L14.4192 6.1905Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M2.40171 8.60352L2.22643 9.69169C2.176 9.98739 2.29588 10.2864 2.53453 10.4602C2.7602 10.626 3.05873 10.6439 3.30167 10.5062L4.20163 10.0028L5.10158 10.507C5.34448 10.645 5.64314 10.6271 5.86872 10.4611C6.10737 10.2872 6.22725 9.98819 6.17682 9.69249L5.99449 8.61925L6.73294 7.85352C6.9371 7.64207 7.00696 7.33031 6.91333 7.04868C6.82772 6.77607 6.59975 6.57606 6.32383 6.53161L5.32246 6.37646L4.86698 5.39348C4.74807 5.12753 4.48892 4.95703 4.20361 4.95703C3.9183 4.95703 3.65915 5.12753 3.54023 5.39348L3.08277 6.37646L2.0814 6.53161C1.80475 6.57539 1.57586 6.77551 1.48992 7.04868C1.3968 7.32984 1.46629 7.64084 1.66953 7.85229L2.40171 8.60352ZM3.22701 7.16916C3.47051 7.13062 3.67927 6.97009 3.78349 6.74117L4.20163 5.83918L4.61897 6.74117C4.72324 6.97009 4.93196 7.13067 5.17545 7.16916L6.14182 7.31907L5.43442 8.05377C5.26305 8.23395 5.18559 8.487 5.22575 8.73529L5.40808 9.76059L4.5486 9.27696C4.33228 9.15502 4.07014 9.15502 3.85377 9.27696L3.01355 9.75011L3.17704 8.73491C3.21702 8.48644 3.13942 8.23329 2.96796 8.05301L2.25318 7.3203L3.22701 7.16916Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M19.8005 5.39348C19.6816 5.12753 19.4225 4.95703 19.1371 4.95703C18.8518 4.95703 18.5927 5.12753 18.4738 5.39348L18.0183 6.37646L17.0169 6.53161C16.741 6.5761 16.513 6.77607 16.4274 7.04868C16.3343 7.32984 16.4038 7.64084 16.607 7.85229L17.3384 8.60352L17.1631 9.69169C17.1127 9.98739 17.2326 10.2864 17.4712 10.4602C17.6969 10.626 17.9954 10.6439 18.2384 10.5062L19.1372 10.0028L20.0371 10.507C20.28 10.645 20.5787 10.6271 20.8043 10.4611C21.0429 10.2872 21.1628 9.98819 21.1124 9.69249L20.9301 8.61925L21.6685 7.85352C21.8727 7.64207 21.9425 7.33031 21.8489 7.04868C21.7633 6.77607 21.5353 6.57606 21.2594 6.53161L20.258 6.37646L19.8005 5.39348ZM20.3704 8.05344C20.199 8.23362 20.1215 8.48667 20.1617 8.73496L20.344 9.76026L19.4845 9.27663C19.2682 9.15469 19.0061 9.15469 18.7897 9.27663L17.9491 9.74775L18.1126 8.73255C18.1525 8.48408 18.0749 8.23093 17.9035 8.05065L17.1886 7.31794L18.1633 7.1668C18.4068 7.12826 18.6155 6.96773 18.7197 6.73881L19.1371 5.83928L19.5544 6.74126C19.6587 6.97019 19.8674 7.13076 20.1109 7.16926L21.0773 7.31916L20.3704 8.05344Z" fill="#5F60B9" stroke="#5F60B9" stroke-width="0.238809"/>
                                                <path d="M8.92101 1.73344C9.05757 1.73419 9.18477 1.66222 9.25689 1.54329C9.32901 1.42437 9.33578 1.27555 9.27471 1.1503L8.88173 0.344233C8.78469 0.145019 8.54857 0.0641625 8.35431 0.163675C8.16005 0.263187 8.0812 0.505332 8.17824 0.704546L8.57123 1.51061C8.6375 1.64658 8.77276 1.73273 8.92101 1.73344Z" fill="#5F60B9"/>
                                                <path d="M7.06581 2.41805C7.22 2.57079 7.46515 2.56862 7.61672 2.41314C7.76833 2.25766 7.77045 2.00631 7.62151 1.84818L7.22852 1.44517C7.07433 1.29243 6.82918 1.29461 6.67762 1.45008C6.52601 1.60556 6.52389 1.85692 6.67283 2.01504L7.06581 2.41805Z" fill="#5F60B9"/>
                                                <path d="M14.4225 1.73133C14.5722 1.73218 14.7093 1.64575 14.7762 1.50846L15.1692 0.702393C15.2662 0.502046 15.1865 0.259003 14.9911 0.159538C14.7958 0.060073 14.5588 0.14178 14.4618 0.342127L14.0688 1.14819C14.0077 1.27349 14.0145 1.42226 14.0866 1.54119C14.1588 1.66011 14.286 1.73209 14.4225 1.73133Z" fill="#5F60B9"/>
                                                <path d="M16.2694 2.42015L16.6624 2.01714C16.7646 1.91592 16.8056 1.76611 16.7696 1.62518C16.7336 1.48425 16.6263 1.37425 16.4889 1.33736C16.3516 1.30048 16.2054 1.34251 16.1067 1.44727L15.7137 1.85027C15.6116 1.95149 15.5706 2.1013 15.6066 2.24223C15.6425 2.38316 15.7498 2.49316 15.8872 2.53005C16.0247 2.56693 16.1707 2.52495 16.2694 2.42015Z" fill="#5F60B9"/>
                                            </svg>
                                            <div class="input-group flex-grow-1">
                                                <input type="number" class="form-control" placeholder="Enter points" min="1" :max="user_points" v-model.number="enteredPoints"/>
                                            </div>
                                            <button class="btn btn-primary px-3" type="button" @click="applyEnteredPoints" :disabled="!canApplyPartialPoints" v-if="!loyaltyPointsApplied">{{ $t('landingpage.apply') }}</button>
                                            <button class="btn btn-danger px-3" type="button" @click="removeLoyaltyPoints" v-else>{{ $t('landingpage.remove') }}</button>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="d-flex justify-content-center flex-wrap gap-3">
                                                    <span v-for="(range, index) in redeem_points.ranges" :key="index" :class="isPointInRange(enteredPoints, range) ? 'text-primary small bg-primary-subtle px-3 py-2 rounded' : 'text-muted small bg-light px-3 py-2 rounded'">
                                                        {{ range.point_from }} To {{ range.point_to }} pts = {{ formatCurrencyVue(range.amount) }} off
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                          <div class="d-flex justify-content-between">
                              <h5 class="m-0 text-capitalize">{{ $t('landingpage.price_detail') }}</h5>
                              <button v-if="SeletedCouponId>0" class="d-block btn btn-sm py-2 btn-danger text-end" @click="RemoveCoupon()" >{{ $t('landingpage.remove_coupon') }}</button>
                          </div>
                          <div  class="table-responsive mt-5">
                              <table class="table">
                                  <tbody>
                                      <tr>
                                          <td class="ps-0"><span class="text-capitalize">{{ $t('messages.Price') }}</span></td>
                                          <td class="pe-0"><span
                                                  class="d-block text-primary text-end">{{formatCurrencyVue(service.price*quantity)}}</span></td>
                                      </tr>
                                      <tr v-if="discount >0">
                                          <td class="ps-0"><span class="text-capitalize">{{ $t('messages.discount') }} ({{service.discount}}% off)</span>
                                          </td>
                                          <td class="pe-0"><span
                                                  class="d-block text-success text-end">-{{formatCurrencyVue(discount)}}</span></td>
                                      </tr>



                                       <tr v-if="props.coupons.length > 0 && SeletedCouponId == 0 && props.service.package_type == null">
  <td class="ps-0">
    <span class="text-capitalize">{{ $t('landingpage.coupon') }}</span>
  </td>
  <td class="pe-0">
    <span class="d-block text-primary text-end cursor-pointer" @click="OpenCouponCardMethod()">
      {{ $t('messages.apply_coupon') }}
    </span>
  </td>
</tr>

<tr v-if="props.coupons.length > 0 && SeletedCouponId > 0 && selectedCoupon != null && props.service.package_type == null">
  <td class="ps-0">
    <span class="text-capitalize cursor-pointer" @click="OpenCouponCardMethod()">
      {{ $t('landingpage.coupon') }} ({{ selectedCoupon.code }})
    </span>
  </td>
  <td class="pe-0">
    <span class="d-block text-success text-end">{{ formatCurrencyVue(coupondiscount) }}</span>
  </td>
</tr>

<tr v-if="OpenCouponCard == 1">
  <td>
   <couponcard
  @getSelectedCoupon="handleCouponResponse"
  :coupons="validCoupons"
  :service_price="service.price * quantity"
  :SeletedCouponId="SeletedCouponId"
/>
  </td>
</tr>

                                      <tr v-if="serviceaddon">
                                          <td class="ps-0"><span class="">{{ $t('landingpage.Add-ons') }}</span></td>
                                          <td class="pe-0"><span
                                                  class="d-block text-primary text-end">{{formatCurrencyVue(addonAmount)}}</span></td>
                                      </tr>

                                      <tr v-if="loyaltyPointsApplied">
                                          <td class="ps-0">
                                            <span class="text-capitalize">{{ $t('landingpage.redeem_discount') }}
                                               <span class="text-primary">
                                                    ({{ pointsUsed }} pts)
                                               </span>
                                            </span>
                                          </td>
                                          <td class="pe-0"><span class="d-block text-success text-end">{{ formatCurrencyVue(loyaltyPointsDiscount) }}</span></td>
                                      </tr>

                                      <tr>
                                          <td class="ps-0"><span class="text-capitalize">{{ $t('landingpage.subtotal') }}</span></td>
                                          <td class="pe-0"><span
                                                  class="d-block text-primary text-end">{{ formatCurrencyVue(subtotal || 0) }}</span></td>
                                      </tr>



                                      <tr >
                                          <td class="ps-0"><span class="text-capitalize">{{ $t('landingpage.tax') }}</span></td>
                                          <td class="pe-0"><span
                                                  class="d-block text-danger text-end"><i v-if="taxAmount>0" class="fa fa-info-circle text-body cursor-pointer" aria-hidden="true" @click="openTaxModel()"></i> +{{ formatCurrencyVue(taxAmount||0)}}</span></td>
                                      </tr>


                                      <tr>
                                          <td class="border-bottom-0 ps-0 pt-3">
                                              <h5 class="m-0 text-capitalize">{{ $t('landingpage.total') }}</h5>
                                          </td>
                                          <td class="border-bottom-0 pe-0 pt-3">
                                              <h5 class="m-0 text-end">{{ formatCurrencyVue(totalAmount)}}</h5>
                                          </td>
                                      </tr>
                                      <tr v-if="service.is_enable_advance_payment == 1">
                                          <td class="border-bottom-0 ps-0 pt-3">
                                              <h5 class="m-0 text-capitalize">{{ $t('messages.advance_payment_amount') }} ({{service.advance_payment_amount}} %)</h5>
                                          </td>
                                          <td class="border-bottom-0 pe-0 pt-3">
                                              <h5 class="m-0 text-end">{{ formatCurrencyVue(advance_payment_amount)}}</h5>
                                          </td>
                                      </tr>

                                  </tbody>
                              </table>
                          </div>
                          <div class="mt-1 pt-md-1 pt-1 text-md-end">
                              <div class="d-inline-flex align-items-center flex-wrap gap-3">
                                <p class="m-0 text-capitalize">{{ $t('messages.wallet_balance') }}:</p>
                                <p class="m-0 text-end">{{ formatCurrencyVue(wallet_amount)}}</p>
                              </div>
                          </div>
                          <div v-if="earn_points > 0" class="mt-3 mb-3">
                            <div class="bg-primary-subtle rounded-3 p-3 border border-success border-2 border-dashed">
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Purple Circle with Coin Icon -->
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <img :src="`${baseUrl}/images/loylity_coin.png`" alt="Loyalty Coin" width="40" height="40" />
                                        </div>
                                    </div>
                                    <!-- Text Content -->
                                    <div class="flex-grow-1">
                                    <p class="mb-1">
                                        {{ $t('landingpage.complete_this_booking_to_earn') }}
                                        <span class="text-primary fw-bold">{{ earn_points }} {{ $t('landingpage.loyalty_points') }}</span>
                                    </p>
                                    <p class="text-muted small mb-0">
                                        {{ $t('landingpage.redeem_points_on_next_booking_for_discounts') }}!
                                    </p>
                                    </div>
                                </div>
                            </div>
                          </div>
                          <div class="mt-5 pt-md-5 pt-3 text-md-end">
                              <div class="d-inline-flex align-items-center flex-wrap gap-3">

                                <a v-if="!service.package_type" :href="`${baseUrl}/service-detail/${service.id}`" class="btn btn-outline-primary">{{ $t('landingpage.cancel') }}</a>
                                <a v-if="service.package_type" :href="`${baseUrl}/service-detail/${service.service_id}`" class="btn btn-outline-primary">{{ $t('landingpage.cancel') }}</a>

                                  <button  type="submit"  v-if="service.is_enable_advance_payment == 1"  class="btn btn-primary"> <span v-if="IsLoading==1" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span v-else>{{ $t('landingpage.pay_advance') }}</span></button>
                                  <button type="submit"  v-else class="btn btn-primary"> <span v-if="IsLoading==1" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span v-else>{{ $t('messages.book_now') }}</span></button>
                              </div>
                          </div>
                      </div>
                    </div>
                    <div class="col-lg-12 text-end" v-else>
                      <button type="submit"  class="btn btn-primary"> <span v-if="IsLoading==1" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span v-else>{{ $t('messages.book_now') }}</span></button>
                    </div>
                  </div>
                </template>
                <component :is="currentComponent" :service="service" :booking_id="bookingId" :customer_id="user_id" :discount="discount"  :total_amount="totalAmount" :advance_payment_amount="advance_payment_amount" :wallet_amount = "wallet_amount" v-if="isChildComponentVisible" />
            </div>
          </form>
        </div>


        <div class="modal fade show couponmodal" id="taxModal" v-if="is_tax == 1" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: block;" >
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">{{$t('messages.applied_taxes')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"  @click="closeModal()"></button>
              </div>
              <div class="modal-body">
                  <div class="d-flex justify-content-between" v-for="tax in taxes" :key="tax">
                    <p>{{ tax.title }}</p>
                    <p v-if="tax.type == 'percent'">{{ formatCurrencyVue(tax.value*subtotal/100) }}</p>
                    <p v-else>{{ formatCurrencyVue(tax.value) }}</p>
                  </div>
              </div>
            </div>
          </div>
        </div>

    </div>
</template>

<script setup>
import { ref, defineProps,computed,onMounted, watch} from 'vue';
import Payment from '../components/Payment.vue';
import FlatPickr from 'vue-flatpickr-component'
import { useField, useForm } from 'vee-validate'
import 'flatpickr/dist/flatpickr.css';
import * as yup from 'yup';
import { STORE_BOOKING_API} from '../data/api';
import couponcard from '../components/CouponCard.vue';
import { confirmcancleSwal} from '../data/utilities';
import Swal from 'sweetalert2-neutral';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import moment from 'moment'
const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
// Fallback route helper using baseUrl and named route path
const shopDetailUrl = (id) => `${baseUrl}/shop-detail/${id}`;
const props = defineProps(['service','coupons','taxes','user_id','availableserviceslot','serviceaddon','googlemapkey','wallet_amount','shop_id','shop_list','user_points','redeem_points']);

const googlemapkey = props.googlemapkey;
const maxDate = computed(() => {
    return props.service.end_at ? new Date(props.service.end_at) : null;
});

// Flatpickr configuration
const config = {
    enableTime: true, // if you want to allow time selection
    dateFormat: 'Y-m-d H:i', // your preferred date format
    minDate: 'today', // Disables past dates AND past times - only current time or future
    maxDate: maxDate.value, // this limits the selection to service.end_at
};

const todos = ref([
  {
    highlight: true,
  },
]);

const is_tax = ref(0)
const openTaxModel = () =>{
  is_tax.value = 1;
}
const closeModal = () =>{
  is_tax.value = 0;
}


onMounted(() => {
    defaultData()
    handleDateSelect(new Date())

    if(props.serviceaddon){
      calculateAddonAmount()
    }

    // Initialize earn points on component mount
    updateEarnPoints();
})
const padZero = (num) => num.toString().padStart(2, '0');

const formatDate = (dateString) => {
  const datefrm = window.dateformate || 'Y-m-d';
  const date = new Date(dateString);

  const year = date.getFullYear();
  const month = padZero(date.getMonth() + 1);
  const day = padZero(date.getDate());

  const ordinalSuffix = (day) => ['th', 'st', 'nd', 'rd'][(day % 10 > 3 || [11, 12, 13].includes(day)) ? 0 : day % 10];

  const formatMap = {
    'Y-m-d': `${year}-${month}-${day}`,
    'm-d-Y': `${month}-${day}-${year}`,
    'd-m-Y': `${day}-${month}-${year}`,
    'd/m/Y': `${day}/${month}/${year}`,
    'm/d/Y': `${month}/${day}/${year}`,
    'Y/m/d': `${year}/${month}/${day}`,
    'Y.m.d': `${year}.${month}.${day}`,
    'd.m.Y': `${day}.${month}.${year}`,
    'm.d.Y': `${month}.${day}.${year}`,
    'jS M Y': `${date.getDate()}${ordinalSuffix(date.getDate())} ${date.toLocaleString('default', { month: 'short' })} ${year}`,
    'M jS Y': `${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}${ordinalSuffix(date.getDate())} ${year}`,
    'D, M d, Y': `${date.toLocaleString('default', { weekday: 'short' })}, ${date.toLocaleString('default', { month: 'short' })} ${day}, ${year}`,
    'D, d M, Y': `${date.toLocaleString('default', { weekday: 'short' })}, ${day} ${date.toLocaleString('default', { month: 'short' })}, ${year}`,
    'D, M jS Y': `${date.toLocaleString('default', { weekday: 'short' })}, ${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}${ordinalSuffix(date.getDate())} ${year}`,
    'D, jS M Y': `${date.toLocaleString('default', { weekday: 'short' })}, ${date.getDate()}${ordinalSuffix(date.getDate())} ${date.toLocaleString('default', { month: 'short' })} ${year}`,
    'F j, Y': `${date.toLocaleString('default', { month: 'long' })} ${date.getDate()}, ${year}`,
    'd F, Y': `${date.getDate()} ${date.toLocaleString('default', { month: 'long' })}, ${year}`,
    'jS F, Y': `${date.getDate()}${ordinalSuffix(date.getDate())} ${date.toLocaleString('default', { month: 'long' })}, ${year}`,
    'l jS F Y': `${date.toLocaleString('default', { weekday: 'long' })} ${date.getDate()}${ordinalSuffix(date.getDate())} ${date.toLocaleString('default', { month: 'long' })} ${year}`,
    'l, F j, Y': `${date.toLocaleString('default', { weekday: 'long' })}, ${date.toLocaleString('default', { month: 'long' })} ${date.getDate()}, ${year}`
  };

  return formatMap[datefrm] || `${year}-${month}-${day}`;
};
const formattedDuration = (value) => {
  if (!value) return '';

  const durationParts = value.split(':');
  const hours = parseInt(durationParts[0]);
  const minutes = parseInt(durationParts[1]);

  // Hide if duration is exactly 0 hrs and 0 min
  if (hours === 0 && minutes === 0) return '';

  if (hours > 0) {
    return `(${hours} hrs${minutes > 0 ? ' ' + minutes + ' min' : ''})`;
  } else {
    return `(${minutes} min)`;
  }
};


const DateFormate = ref(new Date());

const dayName=ref(null)

const dateObject=ref(null)

const availableSlotsForDate = ref([]);

const handleDateSelect = async (value) => {
    if (value == null) {
        todos.value.highlight = true;
    } else {
        dateObject.value = new Date(value);
        const daysOfWeek = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
        dayName.value = daysOfWeek[dateObject.value.getDay()];

        // Fetch available slots for the selected date from API
        if (props.service.is_slot == 1) {
            try {
                const formattedDate = dateObject.value.toISOString().split('T')[0]; // Format: YYYY-MM-DD
                const response = await fetch(`/api/available-slots?date=${formattedDate}&service_id=${props.service.id}`);
                const data = await response.json();
                
                if (data.status && data.data) {
                    // Update the available slots for the selected date
                    availableSlotsForDate.value = data.data.available_slots || [];
                } else {
                    availableSlotsForDate.value = [];
                }
            } catch (error) {
                console.error('Error fetching available slots:', error);
                availableSlotsForDate.value = [];
            }
        }
    }
}



const isChildComponentVisible = ref(false);
const currentComponent = ref(null);

const showChildComponent = () => {
  currentComponent.value = Payment;
  isChildComponentVisible.value = true;
};

const quantity=ref(1)
const OpenCouponCard=ref(0)
const SeletedCouponId=ref(0)
const IsLoading=ref(0)
const selectedCoupon = ref([]);
const bookingId=ref(null)

const OpenCouponCardMethod = () => {
  OpenCouponCard.value = 1
};

const RemoveCoupon=()=>{

    SeletedCouponId.value=0
    selectedCoupon.value=null
}

const addonAmount = ref(0);
const loyaltyPointsApplied = ref(false);
const loyaltyPointsDiscount = ref(0);
const enteredPoints = ref(null);
const pointsUsed = ref(0);

const canApplyFullPoints = computed(() => {
    if(!props.redeem_points.threshold_points == 0){
        return props.user_points >= props.redeem_points.threshold_points && !loyaltyPointsApplied.value;
    }
    return false
});

const canApplyPartialPoints = computed(() => {
  return props.user_points >= props.redeem_points.ranges[0].point_from && !loyaltyPointsApplied.value;
});

const isPointInRange = (points, range) => {
  if (!points || points <= 0) return false;
  return points >= range.point_from && points <= range.point_to;
};

const applyEnteredPoints = () => {
  const points = parseInt(enteredPoints.value);

  if (isNaN(points) || points <= 0) {
    Swal.fire({
      title: 'Invalid Input',
      text: 'Please enter a valid positive number of points.',
      icon: 'warning',
      confirmButtonText: 'OK'
    });
    return;
  }

  if (points > props.user_points) {
    Swal.fire({
      title: 'Insufficient Points',
      text: `You only have ${props.user_points} points available.`,
      icon: 'warning',
      confirmButtonText: 'OK'
    });
    return;
  }

  const matchingRange = props.redeem_points.ranges.find(range =>
    points >= range.point_from && points <= range.point_to
  );

  if (matchingRange) {
    loyaltyPointsDiscount.value = matchingRange.amount;
    loyaltyPointsApplied.value = true;
    pointsUsed.value = points;

    Swal.fire({
      title: 'Success',
      text: `${points} loyalty points applied! Discount of ${formatCurrencyVue(matchingRange.amount)} added.`,
      icon: 'success',
      confirmButtonText: 'OK'
    });
  } else {
    Swal.fire({
      title: 'Invalid Points',
      text: 'The entered points do not fall within any valid range.',
      icon: 'warning',
      confirmButtonText: 'OK'
    });
  }
};

const calculateAddonAmount = () => {
  addonAmount.value = props.serviceaddon
    .filter(addon => !addon.removed)
    .reduce((total, addon) => total + addon.price, 0);
};

const toggleAddon = (index, shouldRemove) => {
  const title = shouldRemove 
    ? 'Do you want to remove this Add-on Service?' 
    : 'Do you want to add this Add-on Service?';
  
  Swal.fire({
    title: title,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes'
  }).then((result) => {
    if (result.isConfirmed) {
      // Toggle the removed state
      props.serviceaddon[index].removed = shouldRemove;
      
      // Recalculate addon amount
      calculateAddonAmount();
      
      Swal.fire({
        title: shouldRemove ? 'Removed' : 'Added',
        text: shouldRemove 
          ? 'Add-on service removed successfully!' 
          : 'Add-on service added successfully!',
        icon: 'success',
        confirmButtonText: 'OK'
      });
    }
  });
};

const applyLoyaltyPoints = () => {
  if (props.user_points >= props.redeem_points.threshold_points) {
    loyaltyPointsDiscount.value = props.redeem_points.max_discount;
    loyaltyPointsApplied.value = true;
    pointsUsed.value = props.redeem_points.threshold_points;

    Swal.fire({
      title: 'Success',
      text: 'Loyalty points discount applied successfully!',
      icon: 'success',
      confirmButtonText: 'OK'
    });
  } else {
    Swal.fire({
      title: 'Insufficient Points',
      text: `You need at least ${props.redeem_points.threshold_points} points to redeem this discount.`,
      icon: 'warning',
      confirmButtonText: 'OK'
    });
  }
};

const removeLoyaltyPoints = () => {
  loyaltyPointsDiscount.value = 0;
  loyaltyPointsApplied.value = false;
  pointsUsed.value = 0;

  Swal.fire({
    title: 'Removed',
    text: 'Loyalty points discount removed successfully!',
    icon: 'success',
    confirmButtonText: 'OK'
  });
};

const increment = () => {
    quantity.value = quantity.value+1
};

const decrement = () => {
    if(quantity.value !=1){
        quantity.value = quantity.value-1
    }
};

const subtotal = computed(() => {
  let baseSubtotal = 0;

  if(props.serviceaddon){
    baseSubtotal = (props.service.price*quantity.value)+addonAmount.value - discount.value
  }else{
    baseSubtotal = (props.service.price*quantity.value) - discount.value
  }

  // Apply coupon discount if available
  if(coupondiscount.value > 0){
    baseSubtotal -= coupondiscount.value;
  }

  // Apply loyalty points discount if available
  if(loyaltyPointsApplied.value && loyaltyPointsDiscount.value > 0){
    baseSubtotal -= loyaltyPointsDiscount.value;
  }

  return baseSubtotal;
});

const earn_points = ref(null);

const updateEarnPoints = async () => {
    try {
        if (props.service) {

            const serviceId = props.service.id;

            const type = (props.service.id && props.service.service_id) ? 'package_service' : 'service';

            const amount = subtotal.value;

            if (serviceId) {
                const url = `${baseUrl}/api/get-earn-points?service_id=${serviceId}&type=${type}&sub_total=${amount}`;

                let headers = {
                    'Accept': 'application/json',
                };

                try {
                    const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content;
                    headers['X-CSRF-TOKEN'] = csrfToken;
                } catch (e) {
                    // Silently continue without CSRF token
                }

                const response = await fetch(url, {
                    method: 'GET',
                    headers: headers,
                });

                if (response.ok) {
                    const data = await response.json();
                    // Update the earn_points value
                    earn_points.value = data.earn_points;
                }
            }
        }
    } catch (error) {
        // Silently handle fetch errors
    }
};

const taxAmount = computed(() => {
  let totalTaxAmount = 0

  if(props.taxes != null && subtotal.value > 0){

    for(const tax of props.taxes) {

        if (tax.type === 'percent') {

           totalTaxAmount += (( subtotal.value) *tax.value) / 100;

        } else {
          totalTaxAmount += tax.value;
        }

      }
        return totalTaxAmount;

    }

    return totalTaxAmount;

});

const discount = computed(() => {

 if(props.service.discount !='' && props.service.discount >0){

     return  props.service.price*quantity.value*props.service.discount/100
 }

 return 0

});


const coupondiscount = computed(() => {

 if(selectedCoupon.value !=null){
    if(selectedCoupon.value.discount_type=='fixed'){
        return  selectedCoupon.value.discount
    }else{

        return  props.service.price*quantity.value*selectedCoupon.value.discount/100
    }
  }

  return 0

 });

 const  totalAmount = computed(() => {
  return taxAmount.value + subtotal.value;
});

const advance_payment_amount = computed(() => {

  const rawValue = totalAmount.value * props.service.advance_payment_amount / 100;
  const roundedValue = Number(rawValue).toFixed(2);
  return parseFloat(roundedValue);

});

const formatCurrencyVue = (value) => {

  if(window.currencyFormat !== undefined) {
    return window.currencyFormat(value)
  }
  return value
}

const formatRating = (value) => {
  const numericValue = Number(value)
  if (!Number.isFinite(numericValue)) {
    return '0.0'
  }
  return numericValue.toFixed(1)
}


const handleCouponResponse = (couponId) => {
  if (couponId != null) {
    const foundCoupon = props.coupons.find(coupon => coupon.id == couponId);

    if (foundCoupon) {
      // Calculate the current total price based on quantity
      const currentTotalPrice = props.service.price * quantity.value;
      let discountAmount = foundCoupon.discount;

      if (foundCoupon.discount_type === 'percent') {
        discountAmount = (foundCoupon.discount / 100) * currentTotalPrice;
      }

      // Reject coupon if discount exceeds price
      if (discountAmount > currentTotalPrice) {
        alert("This coupon cannot be applied because the discount exceeds the service price.");
        return;
      }

      selectedCoupon.value = foundCoupon;
      SeletedCouponId.value = couponId;
    } else {
      selectedCoupon.value = null;
      SeletedCouponId.value = 0;
    }

    OpenCouponCard.value = 0;
  } else {
    SeletedCouponId.value = 0;
    selectedCoupon.value = null;
  }
};


const defaultData = () => {
  errorMessages.value = {}
  return {
    address: '',
    date: new Date(),
    start_time:''
  }
}

// 🕒 Reactive shop times (accessible everywhere)
const shopStartTime = ref(null);
const shopEndTime = ref(null);

const validationSchema = yup.object({
    address: yup.string().required('Address is Required'),

    date: yup.string()
  .test('required-date', 'Date and Time is Required', function (value) {
    if (props.service.is_slot == 0 && !value) {
      return false;
    }


    if (props.service.end_at) {
      const selectedDate = new Date(value);
      const endDate = new Date(props.service.end_at);
      if (selectedDate > endDate) {
        return this.createError({
          message: 'Selected date exceeds the allowed package end date',
        });
      }
    }

    return true;
  })

  .test('within-shop-hours', 'Selected time must be within shop working hours', function (value) {
  // Only enforce shop-hours for on_shop visit type
  if (props.service.visit_type !== 'on_shop') return true;

  // If we don't have working hours configured, skip this check
  if (!shopStartTime.value || !shopEndTime.value) return true;

  // Determine selected time (HH:mm) depending on booking mode
  let timePart = null;

  if (props.service.is_slot == 1) {
    // Slot-based: use sibling field value (start_time is like "19:00:00")
    const slot = this.resolve(yup.ref('start_time'));
    if (!slot) return false;
    timePart = String(slot).slice(0, 5); // "HH:mm"
  } else {
    // Non-slot: use time from flatpickr date string "YYYY-MM-DD HH:mm"
    if (!value) return false;
    timePart = String(value).split(' ')[1];
  }

  if (!timePart) return false;

  // Convert all times into minutes from midnight
  const toMinutes = (t) => {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
  };

  const selected = toMinutes(timePart);
  const start = toMinutes(shopStartTime.value);
  const end   = toMinutes(shopEndTime.value);

  let valid;

  if (end > start) {
    // Normal same-day case
    valid = selected >= start && selected <= end;
  } else {
    // Overnight case (e.g. 15:30 → 03:30)
    valid = selected >= start || selected <= end;
  }

  if (!valid) {
    return this.createError({
      message: `Time must be between ${moment(shopStartTime.value, 'HH:mm').format('h:mm A')} - ${moment(shopEndTime.value, 'HH:mm').format('h:mm A')}`,
    });
  }

  return true;
})
,

   start_time: yup.string().test('start_time', "Please Select Time Slot", function(value) {
       if(props.service.is_slot == 1 && !value  ) {
          return false ;
         }
         return true;
      }),

})

const { handleSubmit, errors, setValues } = useForm({
  validationSchema,
})
const { value: address } = useField('address')
const { value: date } = useField('date')
const { value: start_time } = useField('start_time')
const isLoading = ref(false);
// if (props.shop_address) {
//   address.value = props.shop_address;
// }

// ✅ Default: preselect given shop_id, else first shop, else empty
const selectedShopId = ref(
  props.shop_id ?? (props.shop_list?.[0]?.id ?? '')
);

const selectedShop = computed(() => {
  return props.shop_list.find(shop => shop.id === Number(selectedShopId.value));
});

// Watch for changes
watch(
  selectedShopId,
  (newValue) => {
    const selected = props.shop_list.find(
      shop => shop.id === Number(newValue) // normalize type
    );
    if (selected) {
      const parts = [
        selected.address,
        selected.city?.name,
        selected.state?.name,
        selected.country?.name
      ];
      address.value = parts.filter(Boolean).join(", ");
      // Normalize DB ISO times to HH:mm in admin-configured timezone for validation
      const tz = window.timezone || 'UTC';
      const startUtc = moment.tz ? moment.tz(selected.shop_start_time, tz) : moment(selected.shop_start_time);
      const endUtc = moment.tz ? moment.tz(selected.shop_end_time, tz) : moment(selected.shop_end_time);
      shopStartTime.value = startUtc.format('HH:mm');
      shopEndTime.value = endUtc.format('HH:mm');

      console.log('Selected shop changed:', selected);
    }
  },
  { immediate: true }
);

// Watch for changes in entered points - reset applied state when user changes value
watch(
  enteredPoints,
  (newValue, oldValue) => {
    // If points were already applied and user changes the value, reset the applied state
    if (loyaltyPointsApplied.value && newValue !== oldValue) {
      loyaltyPointsApplied.value = false;
      loyaltyPointsDiscount.value = 0;
    }
  }
);

// Watch for changes in subtotal - update earn points when subtotal changes
watch(
  subtotal,
  () => {
    updateEarnPoints();
  }
);


const getCurrentLocation = async () => {
  isLoading.value = true;

  // Check if geolocation is supported
  if (!navigator.geolocation) {
    Swal.fire({
      title: 'Error',
      text: 'Geolocation is not supported by this browser.',
      icon: 'error',
      confirmButtonText: 'OK'
    });
    isLoading.value = false;
    return;
  }

  // Options for geolocation
  const options = {
    enableHighAccuracy: true,
    timeout: 10000, // 10 seconds timeout
    maximumAge: 0 // Don't use cached position
  };

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        const currentLatitude = position.coords.latitude;
        const currentLongitude = position.coords.longitude;

        // Try multiple geocoding services as fallbacks
        let formattedAddress = null;

        // First, try free OpenStreetMap Nominatim service
        try {
          const nominatimResponse = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${currentLatitude}&lon=${currentLongitude}&addressdetails=1`,
            {
              headers: {
                'User-Agent': 'HandymanServiceApp/1.0'
              }
            }
          );

          if (nominatimResponse.ok) {
            const nominatimData = await nominatimResponse.json();
            console.log('Nominatim response:', nominatimData);

            if (nominatimData && nominatimData.display_name) {
              formattedAddress = nominatimData.display_name;
            }
          }
        } catch (nominatimError) {
          console.log('Nominatim failed, trying next service:', nominatimError.message);
        }

        // If Nominatim fails, try BigDataCloud (free tier)
        if (!formattedAddress) {
          try {
            const bigDataResponse = await fetch(
              `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${currentLatitude}&longitude=${currentLongitude}&localityLanguage=en`
            );

            if (bigDataResponse.ok) {
              const bigDataData = await bigDataResponse.json();
              console.log('BigDataCloud response:', bigDataData);

              if (bigDataData && (bigDataData.locality || bigDataData.city)) {
                const parts = [];
                if (bigDataData.locality) parts.push(bigDataData.locality);
                if (bigDataData.city && bigDataData.city !== bigDataData.locality) parts.push(bigDataData.city);
                if (bigDataData.principalSubdivision) parts.push(bigDataData.principalSubdivision);
                if (bigDataData.countryName) parts.push(bigDataData.countryName);
                formattedAddress = parts.join(', ');
              }
            }
          } catch (bigDataError) {
            console.log('BigDataCloud failed, trying Google Maps if available:', bigDataError.message);
          }
        }

        // If both free services fail, try Google Maps API if key is available and billing is enabled
        if (!formattedAddress && googlemapkey && googlemapkey.trim() !== '') {
          try {
            console.log('Trying Google Maps API as fallback...');
            const response = await fetch(
              `https://maps.googleapis.com/maps/api/geocode/json?latlng=${currentLatitude},${currentLongitude}&key=${googlemapkey}`
            );

            if (response.ok) {
              const data = await response.json();
              console.log('Google Maps response:', data);

              if (data.status === 'OK' && data.results && data.results.length > 0) {
                formattedAddress = data.results[0]?.formatted_address;
              } else {
                console.log('Google Maps API error:', data.status, data.error_message);
              }
            }
          } catch (googleError) {
            console.log('Google Maps API failed:', googleError.message);
          }
        }

        // If all geocoding services fail, use coordinates as fallback
        if (!formattedAddress) {
          formattedAddress = `Lat: ${currentLatitude.toFixed(6)}, Long: ${currentLongitude.toFixed(6)} (Please edit manually)`;

          Swal.fire({
            title: 'Location Detected',
            text: 'Location coordinates detected, but address lookup is unavailable. Please edit the address manually.',
            icon: 'info',
            confirmButtonText: 'OK'
          });
        } else {
          Swal.fire({
            title: 'Success',
            text: 'Current location detected successfully!',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });
        }

        setValues({ address: formattedAddress });

      } catch (error) {
        console.error('Error fetching current location:', error);

        Swal.fire({
          title: 'Location Error',
          text: `Failed to get address: ${error.message}`,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      } finally {
        isLoading.value = false;
      }
    },
    (error) => {
      console.error('Geolocation error:', error);

      let errorMessage = 'Unable to retrieve your location.';

      switch (error.code) {
        case error.PERMISSION_DENIED:
          errorMessage = 'Location access denied. Please enable location permissions in your browser and try again. You can also manually enter your address.';
          break;
        case error.POSITION_UNAVAILABLE:
          errorMessage = 'Location information is unavailable. Please check your internet connection and try again, or manually enter your address.';
          break;
        case error.TIMEOUT:
          errorMessage = 'Location request timed out. Please try again or manually enter your address.';
          break;
        default:
          errorMessage = 'An unknown error occurred while retrieving location. Please manually enter your address.';
          break;
      }

      Swal.fire({
        title: 'Location Access Required',
        text: errorMessage,
        icon: 'warning',
        confirmButtonText: 'OK'
      });

      isLoading.value = false;
    },
    options
  );
};
const cancellation = window.cancellationCharge || {};

const errorMessages = ref({})
const formSubmit = handleSubmit(async(values) => {

    IsLoading.value=1

    const title='Confirm Booking '

    const subtitle='Do you want to Confirm this booking ?'

    let note = '';

    // Cancellation % applies to booking total (after discounts), same as API `bookingUpdate` / `getCancellationCharges`.
    let cancellationCharge = 0;
    if (cancellation['cancellation_charge'] == 1) {
        cancellationCharge = (totalAmount.value * cancellation['cancellation_charge_amount']) / 100;
    }

    // Add note about cancellation charge if applicable
    if (cancellationCharge > 0) {
        note = `A ${formatCurrencyVue(cancellationCharge)} fee applies for cancellation within ${cancellation['cancellation_charge_hours']} hours of the scheduled service.`;
    }


    confirmcancleSwal({ title: title, subtitle:subtitle, text: note }).then(async(result) => {
      IsLoading.value=0
    if (!result.isConfirmed) return
    IsLoading.value=1
    const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content;

    if(props.service.is_slot==1){

        values.date = moment(dateObject.value).format('YYYY-MM-DD')+ ' ' + values.start_time;
        values.booking_slot = values.start_time;
     }

    values.id = '';
    values.customer_id = props.user_id;
    values.service_id = props.service.package_type ? props.service.service_id : props.service.id;
    values.provider_id = props.service.provider_id;
    values.amount = props.service.price;
    values.quantity = quantity.value;
    values.total_amount = totalAmount.value;
    values.discount = props.service.discount;
    values.type = props.service.service_type;
    values.final_total_service_price = props.service.price * quantity.value;
    values.final_total_tax = taxAmount.value;
    values.final_sub_total = subtotal.value;
    values.final_discount_amount = discount.value;
    values.tax =props.taxes;
    values.shop_id = selectedShopId.value;
    values.status = 'pending';

    // Add loyalty points information when applied
    if (props.redeem_points.redeem_type === 'partial') {
        if (loyaltyPointsApplied.value) {
          values.redeemed_points = enteredPoints.value;
          values.redeemed_discount = loyaltyPointsDiscount.value;
        }
    } else {
        if (loyaltyPointsApplied.value) {
          values.redeemed_points = props.redeem_points.threshold_points;
          values.redeemed_discount = loyaltyPointsDiscount.value;
        }
    }

    if (props.service.package_type) {
    const uniqueServiceIds = [
      ...new Set(props.service.package_services.map((service) => service.service_id))
    ];

    values.booking_package = {
      id: props.service.id,
      name: props.service.name,
      is_featured: props.service.is_featured,
      package_type: props.service.package_type,
      price: props.service.price,
      start_at: props.service.start_at,
      end_at: props.service.end_at,
      subcategory_id: props.service.subcategory_id,
      category_id: props.service.category_id,
      service_id: uniqueServiceIds.join(','),
    };
  }
    if(props.serviceaddon){
      values.service_addon_id = props.serviceaddon
        .filter(addon => !addon.removed)
        .map(addon => addon.id);
    }

    if(SeletedCouponId.value>0){
      //values.coupon_id=SeletedCouponId.value
      values.coupon_id=selectedCoupon.value.code
      values.final_coupon_discount_amount=coupondiscount.value

    }else{
        values.coupon_id = ''
     }

     const currentLocale = (typeof window !== 'undefined' && window.frontendLocale) || sessionStorage.getItem('local') || 'en';
     const response = await fetch(STORE_BOOKING_API, {
           method: 'POST',
           headers: {

              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'language-code': currentLocale,
           },
           body:JSON.stringify(values),
        });
        if(response.ok){

          IsLoading.value=0

         const responseData = await response.json();

        if(props.service.is_enable_advance_payment==1){

          bookingId.value=responseData.booking_id

          showChildComponent()

         }else{

          IsLoading.value=0
          Swal.fire({
          title: 'Done',
          text: responseData.message,
          icon: 'success',
          iconColor: '#5F60B9'
        }).then((result) => {

            if (result.isConfirmed) {
               const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
               window.location.href = baseUrl + '/booking-list';
             }

          })

         }

        } else {

          IsLoading.value=0

            Swal.fire({
              title: 'Error',
              text: 'Something Went Wrong!',
              icon: 'error',
              iconColor: '#5F60B9'
            }).then(() => {

            })
        }

     })

})

const validCoupons = computed(() => {
  // Calculate the current total price based on quantity
  const currentTotalPrice = props.service.price * quantity.value;

  return props.coupons.filter(coupon => {
    let discountAmount = coupon.discount;

    if (coupon.discount_type === 'percent') {
      discountAmount = (coupon.discount / 100) * currentTotalPrice;
    }

    // Only show coupons where the discount amount is less than or equal to the current total price
    return discountAmount <= currentTotalPrice;
  });
});
</script>

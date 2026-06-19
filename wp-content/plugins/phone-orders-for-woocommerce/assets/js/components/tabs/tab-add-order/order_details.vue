<template>
    <div id="postbox-container-2" class="postbox-container">
        <div class="postbox disable-on-order" id="woocommerce-order-items">
            <div>
		<div class="wpo-search-options-block" :class="{'wpo-show-search-options': showProductSearchOptions}">
		    <div class="order-details__title" v-html="title" v-show="showHeader"></div>
		    <slot name="before-search-items-field"></slot>
		    <div class="handlediv button-link" v-if="allowAddProducts">
            <a href="#" class="link-add-item-from-shop"
                v-show="isAllowAddProductsFromShopPage" @click="configureProduct(null)">
                {{ addProductFromShopTitle }}
            </a>
			<a href="#" class="link-add-custom-item"
			   @click.prevent="cartEnabled ? addCustomProductItem() : null" :class="{disabled: !cartEnabled}">
			    {{ addProductButtonTitle }}
			</a>
		    </div>
		</div>
            </div>
            <div style="clear: both"></div>
            <div class="inside">
                <div class="order-content">
		    <div v-show="isBarcodeMode" class="wpo-barcode-mode-alert">
			{{ barcodeModeAlertMessage }}
		    </div>
                    <div id="search-items-box" :class="{'display-find-results-as-grid': displayProductsFindResultsAsGrid, 'wpo-show-product-search-options': showProductSearchOptions, 'find-results-full-screen-mode': isOpenSearchMultipleSelectedProducts}">
                        <input type="hidden" id="additional_parameters_for_select_items" value="{}">
			<div class="wpo-find-product-search-block">

                        <multiselect
			    ref="productSelectSearch"
			    style="width: 100%;"
			    label="title"
			    v-model="product"
			    :options="productsList"
			    track-by="value"
			    id="ajax-search-product"
			    :placeholder="findProductsSelectPlaceholder"
			    :loading="isLoading"
			    :internal-search="false"
			    :show-no-results="true"
			    @search-change="updateProductSearch"
			    :hide-selected="false"
			    :searchable="true"
			    open-direction="bottom"
			    @input="isAllowConfigureProduct ? (useConfigureProductActionAsDefault ? configureProduct() : null) : addProductItemsToCart([product])"
			    :disabled="!cartEnabled"
			    :options-limit="+productSelectOptionsLimit"
			    :close-on-select="productSelectCloseOnSelected"
			    :clear-on-select="productSelectCloseOnSelected"
			    :allow-empty="false"
			    :show-labels="false"
			    @close="closeMultipleSelectedProducts"
			    :preserve-search="isExistsAdditionalProductSearchParams"
			    :block-keys="['Delete']"
			    v-store-search-multiselect
                        >
                            <span slot="noResult" ref="productSearchNoResult">{{ noResultLabel }}</span>
                            <template slot="singleLabel" slot-scope="props">
                                <span v-html="props.option.title"></span>
                            </template>
                            <template slot="option" slot-scope="props">
				<label class="option__element__full-screen-mode">
				    <span class="option__block" @mousedown.stop @click.stop="isAllowConfigureProduct && useConfigureProductActionAsDefault ? configureProduct(props.option) : null">
					<span class="option__checkbox">
					    <input type="checkbox" v-model="multipleSelectedProducts[props.option.value]['checked']">
					</span>
					<img class="option__image" :src="props.option.img" alt="" v-show="!!props.option.img"
					     width="100">
					<span class="option__desc">
					    <span v-if="defaultActionClickOnTitleProductItemInSearchProducts === 'edit_product'">
						<a :href="props.option.product_link" target="_blank" @mousedown.stop @click.stop>
						    <span class="option__title option__title" v-html="props.option.title"></span>
						</a>
					    </span>
					    <span v-else-if="defaultActionClickOnTitleProductItemInSearchProducts === 'view_product'">
						<a :href="props.option.permalink" target="_blank" @mousedown.stop @click.stop>
						    <span class="option__title option__title" v-html="props.option.title"></span>
						</a>
					    </span>
					    <span v-else>
						<span class="option__title option__title" v-html="props.option.title"></span>
					    </span>
					</span>
					<button class="btn btn-light wpo-btn-configure-product" @click="configureProduct(props.option)" v-show="isAllowConfigureProduct && !useConfigureProductActionAsDefault">
					    {{ findProductsSelectButtonConfigureLabel }}
					</button>
				    </span>
				</label>
				<span class="option__block option__element__default">
				    <img class="option__image" :src="props.option.img" alt="" v-show="!!props.option.img"
					 width="100">
				    <span class="option__desc">
					<span>
					    <span class="option__title option__title" v-html="props.option.title"></span>
					</span>
				    </span>
				 </span>
                            </template>
                            <template slot="noOptions">
                                <span v-html="noOptionsTitle"></span>
                            </template>
                            <template slot="beforeList">
				<div class="find-results-full-screen-mode__search" @mousedown.stop @click.stop>
				    <slot name="before-search-items-field"></slot>
				    <div class="find-results-full-screen-mode__search__block">
					<div class="multiselect__spinner" v-show="isLoading"></div>
					<input type="text" class="find-results-full-screen-mode__search__input" style="width: 100%" v-model="searchMultipleSelectedProducts" ref="searchMultipleSelectedProducts" :placeholder="findProductsSelectPlaceholder">
				    </div>
				</div>
			    </template>
                            <template slot="afterList">
				<div class="find-results-full-screen-mode__actions">
				    <div>
					<strong>{{ multipleSelectedProductsCountLabel }}:</strong>
					{{ multipleSelectedProductsCount }}
				    </div>
				    <div class="find-results-full-screen-mode__actions__buttons">
					<button class="btn btn-primary" @click="addMultipleSelectedProducts">
					    {{ addMultipleSelectedProductsLabel }}
					</button>
					<button class="btn btn-light" @click="cancelMultipleSelectedProducts">
					    {{ cancelMultipleSelectedProductsLabel }}
					</button>
				    </div>
				</div>
                            </template>
                        </multiselect>

			    <button id="wpo-advanced-search-button" class="btn btn-primary" @click="cartEnabled ? openSelectSearchProduct() : null" :class="{disabled: !cartEnabled}">
				{{ browseProductsMultipleSelectedProductsLabel }}
			    </button>
			</div>
			<div class="wpo-find-product-select-buttons" v-show="isAllowConfigureProduct && product && !useConfigureProductActionAsDefault">
			    <button class="btn btn-primary" @click="cartEnabled ? addProductToOrder(product) : null" :class="{disabled: !cartEnabled}">
				{{ findProductsSelectButtonAddToOrderLabel }}
			    </button>
			    <button class="btn btn-primary" @click="cartEnabled ? configureProduct(product) : null" :class="{disabled: !cartEnabled}">
				{{ findProductsSelectButtonConfigureLabel }}
			    </button>
			</div>
                    </div>
                    <div class="woocommerce_order_items_wrapper wc-order-items-editable">
                        <table cellpadding="0" cellspacing="0" class="woocommerce_order_items" ref="woocommerceOrderItems">
				<thead>
				    <tr>
                    <th class="move-line-th"><span class="wpo-move-line-item-column-header">&nbsp;</span></th>
					<th class="name sortable" colspan="2" data-sort="string-ins">
					    {{ productsTableItemColumnTitle }}
					</th>
					<th class="item_extra_col sortable" data-sort="float" v-if="showProductsTableExtraColumn">
					    {{ productsTableExtraColumnTitle }}
					</th>
					<th class="item_discount sortable" data-sort="float" v-if="showColumnDiscount">
					    {{ columnDiscountTitle }}
					</th>
					<th class="item_cost sortable" data-sort="float">
					    <span class="wpo-item-cost-column-header">
						{{ productsTableCostColumnTitle }}
					    </span>
					</th>
					<th class="quantity sortable" data-sort="int">
					    <span class="wpo-quantity-column-header">
						{{ productsTableQtyColumnTitle }}
					    </span>
					</th>
					<th class="line_cost sortable" data-sort="float">
					    <span class="wpo-line-cost-column-header">
						{{ productsTableTotalColumnTitle }}
					    </span>
					</th>
					<th class="wc-order-edit-line-item"><span class="wpo-edit-line-item-column-header">&nbsp;</span></th>
				    </tr>
				</thead>
			    </table>
			    <div class="woocommerce_order_items__block-items" v-bind:class="{scrollable: scrollableCartContentsOption}">
				<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
                    <draggable tag="tbody" @end="changeProductItemsOrder" handle=".handle" :disabled="!cartEnabled">
					<product-item
						v-bind="productItemLabels"
						v-for="product in productList"
						:item="Object.assign({}, product)"
						:calculated="getProductItemObject(product)"
						:key="getProductKey(product)"
						:ref="getProductRef(product)"
						:show-products-table-extra-column="showProductsTableExtraColumn"
						:show-column-discount="showColumnDiscount"
						:editable-custom-meta-fields="getEditableProductItemCustomMetaFields(product)"
						@update-editable-custom-meta-fields="updateEditableProductItemCustomMetaFields"
						:product-subscription-options="productSubscriptionOptions"
					></product-item>
                    </draggable>
				</table>
			    </div>
			</div>
                    </div>
                    <div class="order-details-cart-buttons">
                        <div class="order-details-cart-buttons__block">
                            <copy-cart-button
                                    :default-button-label="copyCartButtonLabel"
                                    :copied-button-label="copyCopiedCartButtonLabel"
                            ></copy-cart-button>
                            <slot name="wpo-after-order-items"></slot>
                        </div>
                    </div>
                </div>
                <div class="order-footer row">
		    <div class="col-12 col-md-7">
                        <slot name="order-footer-left-side"></slot>
                        <div class="order-footer__note">
                            <p class="wpo-customer-provided-note">
                                {{ customerProvidedNoteLabel }}
                                <textarea :placeholder="customerProvidedNotePlaceholder"
                                          v-model.lazy="customerProvidedNote" v-bind:disabled="!cartEnabled"></textarea>
                            </p>
                            <p class="wpo-customer-private-note" v-if="showPrivateNote">
                                {{ customerPrivateNoteLabel }}
                                <textarea :placeholder="customerPrivateNotePlaceholder"
                                          v-model.lazy="customerPrivateNote" v-bind:disabled="!cartEnabled"></textarea>
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <table class="wc-order-totals">
                            <tbody>
                            <tr>
                                <td class="label-total">{{ subtotalLabel }}:</td>
                                <td width="1%"></td>
                                <td class="subtotal">
                                    <strong v-html="wcPrice(subtotal)"></strong>
                                </td>
                                <td class="total-value subtotal">
                                    <strong v-html="wcPrice(subtotalWithTax)"></strong>
                                </td>
                            </tr>
                            <tr v-if="couponsEnabled" class="coupons-list-item" v-for="(coupon, index) in couponList">
                                <td class="label-total">Coupon : {{ coupon.title }}</td>
                                <td width="1%"></td>
                                <td>
                                        <span class="woocommerce-Price-amount coupon-value">
                                            <span v-if="coupon.amount" v-html="wcPrice(coupon.amount)"></span>
                                            <a class="remove-coupon" href="#"
                                               @click.prevent.stop="cartEnabled ? removeCoupon(coupon, index) : null"
                                               :class="{disabled: !cartEnabled}">[{{removeLabel}} ]</a>
                                        </span>
                                </td>
                            </tr>
                            <tr v-if="couponsEnabled && !hideAddCoupon" class="coupons-list-add">
                                <td class="label-total">
                                    <a href="#" @click.prevent.stop="cartEnabled ? addCoupon() : null"
                                       :class="{disabled: !cartEnabled}">
                                        {{ addCouponLabel }}
                                    </a>
                                </td>
                                <td width="1%"></td>
                                <td><span class="woocommerce-Price-amount coupon-value"></span></td>
                            </tr>
                            <tr v-if="!hideAddDiscount && couponsEnabled">
                                <td class="label-total">
                                    <a href="#" @click.prevent.stop="cartEnabled ? addDiscount() : null"
                                       :class="{disabled: !cartEnabled}">
					<span v-if="discount && discountAmount">
					    {{ manualDiscountLabel }}
					    (<span v-if="discount.type === 'fixed_cart'"><span v-html="currencySymbol"></span>{{ discount.amount }}</span><span v-else>{{ discount.amount }}%</span>) :
					</span>
					<span v-else>
					    {{ addDiscountLabel }}
					</span>
                                    </a>
                                </td>
                                <td width="1%"></td>
                                <td>
                                    <strong v-show="discount && discountAmount" v-html="wcPrice(discountAmount)"></strong>
                                </td>
                            </tr>
                            <tr v-if="!couponsEnabled && !hideCouponWarning" class="coupons-apply-warning">
                                <td colspan="4" v-html="activateCouponsLabel"></td>
                            </tr>
                            <tr class="fee-list-item" v-for="(fee, index) in feeList">
                                <td class="label-total"><span class="fee-wrapper">{{feeNameLabel}} :</span> {{ fee.name }}</td>
                                <td width="1%"></td>
                                <td>
                                        <span class="woocommerce-Price-amount fee-value">
                                            <a class="remove-fee" href="#"
                                               @click.prevent.stop="cartEnabled ? removeFee(fee, index) : null"
                                               :class="{disabled: !cartEnabled}">
                                                [{{removeLabel}} ]
                                            </a>
					    <span v-html="wcPrice(fee.amount)"></span>
                                        </span>
                                </td>
                                <td class="total-value">
				    <span class="woocommerce-Price-amount fee-value-no-action" v-html="wcPrice(fee.amount_with_tax || fee.amount)"></span>
                                </td>
                            </tr>
                            <slot name="add-fee"></slot>
                            <tr>
                                <td colspan="4">
                                    <button id="recalculate" class="btn btn-primary" data-action="recalculate"
                                            v-show="!autoRecalculate" :disabled="!cartEnabled || !productList.length"
                                            @click="recalculate">
                                        {{ recalculateButtonLabel }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!hideAddShipping">
				    <td class="label-total">
					    {{ shippingLabel }}
				    </td>
				    <td width="1%"/>
				    <td/>
				    <td class="total-value"/>
			    </tr>

			    <tr v-if="!hideAddShipping" v-for="(shippingPackage, index) in shipping.packages">
				    <td class="label-total">
					    <a href="#" @click.prevent.stop="cartEnabled ? addShipping(shippingPackage.hash) : null"
					       :class="{disabled: !cartEnabled}">
                            {{ shippingPackage.chosen_rate ? (shipping.is_free_shipping_coupon_applied ? getRateLabelGrantedByCoupon(shippingPackage.chosen_rate.label) : shippingPackage.chosen_rate.label) : addLabel }}
					    </a>
					    <span v-if="shipping.packages.length > 1" class="total-shipping-label">{{ calculateShippingLabel(shippingPackage) }}</span>
				    </td>

				    <td width="1%"/>

				    <td>
					    <span class="shipping-value amount" v-if="shippingPackage.chosen_rate && shippingPackage.chosen_rate.cost" v-html="wcPrice(shippingPackage.chosen_rate.cost)"/>
				    </td>

				    <td class="total-value">
					    <span class="shipping-value amount" v-if="shippingPackage.chosen_rate && shippingPackage.chosen_rate.full_cost" v-html="wcPrice(shippingPackage.chosen_rate.full_cost)"/>
				    </td>
			    </tr>
                            <tr class="order-discount-line order-total-line--updated">
                                <td class="label-total">{{ discountLabel }}:</td>
                                <td width="1%"></td>
                                <td class="total" style="border-top: 1px solid grey;" v-html="wcPrice(totalDiscount)"></td>
                            </tr>
                            <tr v-if="showTotalTax" class="order-taxes-line order-total-line--updated">
                                <td class="label-total">{{ taxLabel }}:</td>
                                <td width="1%"></td>
                                <td class=""></td>
                                <td class="total total-value" v-html="wcPrice(totalTax)"></td>
                            </tr>

                            <tr v-if="showTaxTotalsOption" class="order-taxes-line order-total-line--updated" v-for="(rateTotal, code) in taxTotals">
                                <td class="label-total"><span v-if="!showTotalTax">{{ taxLabel }}:</span></td>
                                <td width="1%"></td>
                                <td class="">
                                    <span>{{ code }}</span><span>({{rateTotal.formatted_percent}})</span>
                                </td>
                                <td class="total total-value" v-html="wcPrice(rateTotal.amount)"></td>
                            </tr>

                            <tr v-for="additional in additionalData">
                                <td class="label-total" v-html="additional.title"></td>
                                <td width="1%"></td>
                                <td class="" v-html="additional.value_without_tax"></td>
                                <td class="total total-value" v-html="additional.value_total"></td>
                            </tr>

                            <tr class="order-total-line order-total-line--updated">
                                <td class="label-total">{{ orderTotalLabel }}:</td>
                                <td width="1%"></td>
                                <td class="total" v-html="wcPrice(orderTotal)"></td>
                                <td class="total total-value" v-html="wcPrice(orderTotalWithTax)"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="order-actions" v-show="showOrderActions">
                    <table class="wc-order__actions">
                        <tr>
                            <td>
                                    <span class="description">
                                        <span class="description-content">
                                            <b-alert :show="!!buttonsMessage"
                                                     fade
                                                     variant="success"
                                            >
                                                {{ buttonsMessage }}
                                           </b-alert>
                                            <b-alert :show="dismissCountDown"
                                                     fade
                                                     dismissible
                                                     variant="danger"
                                                     @dismissed="dismissCountDown"
                                                     @dismiss-count-down="countDownChanged"
                                            >
                                                {{ errorMessage }}
                                           </b-alert>
                                            <b-alert :show="!!checkoutLink"
                                                     fade
                                                     variant="info"
						     class="checkout-link-block"
                                            >
						<b-input-group>
						    <b-form-input size="sm" :value="checkoutLink" :readonly="true" ref="checkoutLinkInput"></b-form-input>
						    <b-input-group-append>
							<b-button size="sm" variant="light" @click="copyCheckoutLink" :title="copyCheckoutLinkTitle">
							    <fa-icon :icon="['far', 'copy']"/>
							</b-button>
						    </b-input-group-append>
						</b-input-group>
                                           </b-alert>
                                        </span>
                                    </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <button class="btn btn-primary" @click="onCreateOrder"
                                        v-show="showCreateOrderButton && showCreateOrderButtonOption">
                                    {{ createOrderButtonLabel }}
                                </button>

                                <slot name="pro-version-buttons-1"></slot>

                                <button class="btn btn-primary" @click="viewOrder" v-show="showViewOrderButton">
                                    {{ viewOrderButtonLabel }}
                                </button>

                                <slot name="pro-version-buttons-2"></slot>

                                <button class="btn btn-primary" @click="sendOrder" v-show="showSendOrderButton">
                                    {{ sendOrderButtonLabel }}
                                </button>

                                <slot name="pro-version-buttons-3"></slot>

                                <button class="btn btn-primary" @click="duplicateOrder" v-show="showDuplicateOrder">
                                    {{ duplicateOrderLabel }}
                                </button>

                                <button class="btn btn-primary" @click="createNewOrder"
                                        v-show="showCreateNewOrderButton">
                                    {{ createNewOrderLabel }}
                                </button>

				<slot name="pro-version-buttons-4"></slot>

                                <div data-action="pay-order" v-show="!isProVersion">
                                    <br>
                                    <b>{{ payOrderNeedProVersionMessage }}</b>
                                    <a href="https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/"
                                       target=_blank>
                                        {{ buyProVersionMessage }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    #woocommerce-order-items .button-link {
        text-decoration: none;
    }
    .wc-order-totals .coupons-apply-warning a {
        color: red;
        border: 1px solid red;
        padding: 5px;
    }

    .woocommerce_order_items tr td:first-child,
    .woocommerce_order_items tr th:first-child {
        width: 5%;
    }

    .woocommerce_order_items tr td.thumb {
        width: 10%;
    }

    .woocommerce_order_items tr td.name {
        width: 50%;
    }

    .woocommerce_order_items tr td.item_cost div,
    .woocommerce_order_items tr td.quantity div,
    .woocommerce_order_items tr td.line_total div,
    .woocommerce_order_items tr th.item_cost,
    .woocommerce_order_items tr th.quantity,
    .woocommerce_order_items tr th.line_total,
    .woocommerce_order_items tr th.item_discount {
        text-align: center !important;
    }


    .order-details-cart-buttons {
        text-align: right;
        margin: 10px;
    }

    .order-details-cart-buttons .order-details-cart-buttons__block {
        display: inline-block;
        text-align: left;
    }

    .order-details-cart-buttons .order-details-cart-buttons__block .btn + .btn {
        margin-left: 10px;
    }

    .wpo-find-product-select-buttons {
	margin-top: 10px;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element {
	display: inline-block;
	width: 100%;
	margin-top: 10px;
	border: solid 3px #eee;
	margin-left: 5px;
	margin-right: 5px;
	vertical-align: top;
	text-align: center;
	border-radius: 8px;
	box-shadow: 0 0 10px #eee;
	max-width: 140px;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .multiselect__option {
	padding: 0;
	min-height: 250px;
	border-radius: 8px;
    }

    #woocommerce-order-items .display-find-results-as-grid .option__desc {
	display: block;
	margin-top: 15px;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .option__image {
	width: 100%;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .multiselect__option {
	white-space: normal;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .multiselect__option .option__title__grid__price {
	margin-top: 15px;
	font-weight: bold;
    }

    #woocommerce-order-items .search_option .multiselect__clear {
        z-index: 1;
    }

    #woocommerce-order-items .display-find-results-as-grid .option__checkbox {
	position: absolute;
	right: 18px;
	top: 16px;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .option__block {
	padding: 18px;
	display: block;
	min-height: 250px;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .option__quantity {
	margin-top: 20px;
	display: block;
	text-align: center;
    }

    #woocommerce-order-items .display-find-results-as-grid .multiselect__element .option__quantity input {
	width: 50%;
	text-align: center;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper {
	overflow: hidden;
	display: flex;
	flex-flow: column;
    }

    #woocommerce-order-items .find-results-full-screen-mode .find-results-full-screen-mode__search__block {
	position: relative;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__element .multiselect__option > label,
    #woocommerce-order-items .find-results-full-screen-mode .multiselect__element .multiselect__option > label .option__block {
	display: block;
    }

    #woocommerce-order-items .display-find-results-as-grid .find-results-full-screen-mode__search .multiselect__element {
	display: block;
	width: 100%;
	margin: 0;
	border: none;
	vertical-align: top;
	text-align: left;
	border-radius: 0;
	box-shadow: none;
	max-width: 100%;
    }

    #woocommerce-order-items .display-find-results-as-grid .find-results-full-screen-mode__search .multiselect__element .multiselect__option {
	padding: 7px;
	min-height: 30px;
	border-radius: 0;
    }

    #woocommerce-order-items .find-results-full-screen-mode .find-results-full-screen-mode__search .multiselect__content-wrapper {
	padding: 0;
	overflow: auto;
    }

    #woocommerce-order-items .multiselect > .multiselect__select {
	transform: none;
    }

    #woocommerce-order-items .multiselect--active > .multiselect__select {
	transform: rotate(180deg);
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content {
	overflow: auto;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .find-results-full-screen-mode__actions .find-results-full-screen-mode__actions__buttons {
	text-align: right;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .find-results-full-screen-mode__search__input {
	width: 100%;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .find-results-full-screen-mode__search__block {
	position: relative;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .multiselect__spinner {
	height: 25px;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .multiselect__option--highlight a {
	color: white;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .multiselect__option .wpo-btn-configure-product {
	position: absolute;
	right: 10px;
	bottom: 40px;
	display: none;
    }

    #woocommerce-order-items .find-results-full-screen-mode.display-find-results-as-grid .multiselect__content-wrapper .multiselect__content .multiselect__option .wpo-btn-configure-product {
	position: absolute;
	left: 3px;
	bottom: 20px;
	display: none;
    }

    #woocommerce-order-items .find-results-full-screen-mode.display-find-results-as-grid .multiselect__content-wrapper .multiselect__content .multiselect__option--highlight .wpo-btn-configure-product,
    #woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__content .multiselect__option--highlight .wpo-btn-configure-product {
	display: block;
    }

    .wpo-find-product-search-block {
	display: flex;
    }

    .wpo-find-product-search-block > .btn {
	margin-left: 20px;
	flex-shrink: 0;
    }

    #woocommerce-order-items .wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper .find-results-full-screen-mode__search,
    #woocommerce-order-items .wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper .find-results-full-screen-mode__actions {
	display: none;
    }

    #woocommerce-order-items .find-results-full-screen-mode .wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper .find-results-full-screen-mode__search {
	display: block;
	padding: 10px;
    }

    #woocommerce-order-items .find-results-full-screen-mode .wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper .find-results-full-screen-mode__actions {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 10px;
	flex-shrink: 0;
    }

    #woocommerce-order-items .find-results-full-screen-mode .wpo-find-product-search-block > .btn {
	display: none;
    }

    #woocommerce-order-items .multiselect__element .multiselect__option .option__element__full-screen-mode,
    #woocommerce-order-items .find-results-full-screen-mode .multiselect__element .multiselect__option .option__element__default {
	display: none;
    }

    #woocommerce-order-items .find-results-full-screen-mode .multiselect__element .multiselect__option .option__element__full-screen-mode,
    #woocommerce-order-items .multiselect__element .multiselect__option .option__element__default {
	display: block;
    }

    .wpo-search-product-list-full-screen-mode {
	overflow: hidden;
    }

    .woocommerce_order_items__block-items {
	overflow: auto;
	border-bottom: 1px solid #dfdfdf;
    }

    .woocommerce_order_items__block-items.scrollable {
        max-height: 340px;
    }

    @media (max-width:1700px){
	#woocommerce-order-items .display-find-results-as-grid .multiselect__element {
	    margin-left: 2px;
	    margin-right: 2px;
	}
    }

    #woocommerce-order-items .find-results-full-screen-mode .wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper > .multiselect__content {
	padding: 0 10px;
	flex-grow: 1;
    }

    body.mobile.modal-open #wpwrap {
	overflow: hidden;
	position: relative;
	height: auto;
    }

    body .modal {
	z-index: 100000;
    }

    #phone-orders-app .find-results-full-screen-mode__search__block > .multiselect__spinner {
	height: 25px;
    }

    #find_order_div {
	display: flex;
	align-items: center;
	margin: 5px;
    }

    #find_order_div .flex_block {
	width: 350px;
	height: 100%;
	margin: 5px;
    }

    .wpo-search-options-block {
	display: flex;
	align-items: center;
	padding: 12px 12px 0;
	justify-content: space-between;
    }

    .wpo-search-options-block .button-link {
	flex-shrink: 0;
	margin-left: 20px;
    }

    .wpo-search-options-block .search_options {
	flex-grow: 1;
    }

    .wpo-search-options-block.wpo-show-search-options .button-link {
	padding-top: 12px;
    }

    @media (max-width:767px){

	#find_order_div {
	    flex-wrap: wrap;
	    justify-content: flex-start;
	}

	#find_order_div .flex_block {
	    width: 100%;
	    display: block;
	}

	#woo-phone-orders #find_order_div .edit-order__button {
	    margin-left: 0;
	}

	#woo-phone-orders #find_order_div .btn {
	    margin-top: 10px;
	}

	.wpo-find-product-search-block {
	    flex-wrap: wrap;
	    justify-content: flex-end;
	}

	.wpo-find-product-search-block > .btn {
	    margin-left: 0;
	    margin-top: 10px;
	}

	.wc-order__actions .btn {
	    margin-bottom: 10px;
	}

	#woocommerce-order-items .display-find-results-as-grid .multiselect__element {
	    max-width: 130px;
	}

	#woocommerce-order-items .display-find-results-as-grid .multiselect__element .multiselect__option,
	#woocommerce-order-items .display-find-results-as-grid .multiselect__element .option__block {
	    min-height: 230px;
	}

	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items th.item_cost,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items th.quantity,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.thumb,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item_cost,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.quantity,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items .product-item-meta-field-list,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items th.item_discount,
	#woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items td.item_discount {
	    display: none;
	}

	.wpo-search-options-block {
	    flex-wrap: wrap;
	    flex-direction: column-reverse;
	    align-items: flex-end;
	}

	.wpo-search-options-block .button-link {
	    margin-left: 0;
	    margin-bottom: 10px;
	}

	.wpo-search-options-block.wpo-show-search-options .button-link {
	    padding-top: 0;
	}
    }

    @media (max-width:1023px){
	#woocommerce-order-items .find-results-full-screen-mode .multiselect__content-wrapper .multiselect__spinner {
	    height: 18px;
	}
    }

    @media (min-width: 576px) and (max-width:1023px) {
	#addFee .form-inline .form-control {
	    max-width: 100px;
	}
    }

    .checkout-link-block .form-control {
	background-color: white;
	font-weight: normal;
    }

    .checkout-link-block .btn {
	border: solid 1px #ddd;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper .woocommerce_order_items th.item_discount {
	font-weight: bold;
	text-transform: uppercase;
	color: #333;
    }

    #woocommerce-order-items .wpo-barcode-mode-alert {
	margin: 0 12px -10px;
	color: red;
    }
</style>

<script>

    import Multiselect from 'vue-multiselect';
    import ProductItem from './product_item.vue';
    import CopyCartButton from './copy_cart_button.vue';
    import draggable from 'vuedraggable';
    import _ from 'lodash';

    import {library} from '@fortawesome/fontawesome-svg-core';
    import {faCopy} from '@fortawesome/free-regular-svg-icons';
    import {FontAwesomeIcon as FaIcon} from '@fortawesome/vue-fontawesome';

    library.add(faCopy)

    export default {
        created: function () {

            this.$root.bus.$on('check-valid', (callback) => {
                if ( ! this.isCartValid() ) {
	                this.showAlert();
                    this.errorMessage = this.isValidCartErrorMessage;
                    return;
                } else {
                    callback();
                }
            });

            this.$root.bus.$on('open-search-product', () => {
                this.openProductSearchSelect();
            });

            this.$root.bus.$on('clear-cart', () => {
                // fix unavailable to use same product in new order (after create new order click)
                this.selectedItems = [];

                !this.autoRecalculate && this.recalculate();
            });

            this.$root.bus.$on('clear-calculated-item', (key) => {
                this.calculatedItems = this.calculatedItems.filter((item) => {
                    return item.key !== key;
                });
            });

            this.$root.bus.$on('clear-selected-item', (id) => {

		var deleted = false;

                this.selectedItems = this.selectedItems.filter((item) => {

		    if (item !== id || deleted) {
			return true;
		    }

		    deleted = true;

		    return false;
                });
            });

            this.$root.bus.$once(['cart-inited'], (params) => {

		this.$root.bus.$on('recalculate-cart', () => {
		    if (this.autoRecalculate) {
			this.recalculate();
		    } else {
			this.manualRecalculate();
		    }
		});

		this.$nextTick(() => {
		    this.disableLoadDefaultFindProducts = false;
		    this.loadFindProducts(this.defaultFindProductsIDs);
		});
	    });

            this.$root.bus.$on('apply-recalculated-cart', (data) => {
                this.recalculateCallback(data);
            });

            this.$root.bus.$on('set-manual-discount', (discount) => {
                this.discountAmount = ! discount ? 0 : (discount.type === 'percent' ? this.subtotal * discount.amount / 100 : discount.amount);
            });

            this.$store.commit('add_order/setLogRowID', this.logRowID);

            this.$root.bus.$on(['app-loaded'], () => {
		if (!this.getParameter( "edit_order_id" ) && !this.getParameter( "restore_cart" )) {
		    var callback = () => { this.$root.bus.$emit('cart-inited'); };
		    this.initEmptyCart(callback, callback);
		}
            });

            this.$root.bus.$on(['create-new-order', 'clear-all'], (params) => {
                this.initEmptyCart(params && params.callback ? params.callback : null);
            });

            this.$root.bus.$on('configure-product-close', (product) => {
		this.addConfiguredProduct();
            });
            this.$root.bus.$on('marked-as-paid', (data) => {
                this.buttonsMessage = data.message;
            })

            this.$root.bus.$on('show-error-message', (message) => {
		this.showAlert();
		this.errorMessage = message;
            });

            this.$root.bus.$on('show-checkout-link', (checkout_url) => {
		this.checkoutLink = checkout_url;
		setTimeout(() => {
		    this.checkoutLink = null;
		}, 5000)
            });
        },
        mounted: function () {

        if(this.scrollableCartContentsOption) {
            var scrollable = window.document.querySelectorAll('.woocommerce_order_items__block-items');

            scrollable.forEach((item) => {
            item.addEventListener('wheel', function(event) {
                var deltaY = event.deltaY;
                var contentHeight = item.scrollHeight;
                var visibleHeight = item.offsetHeight;
                var scrollTop = item.scrollTop;

                if (scrollTop === 0 && deltaY < 0) {
                event.preventDefault();
                } else if (visibleHeight + scrollTop >= contentHeight && deltaY > 0) {
                event.preventDefault();
                }
            });
            });
        }

	    var advancedSearchPopup = window.document.querySelectorAll('.wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper')[0];
	    var advancedSearchInputBlock     = window.document.querySelectorAll('.wpo-find-product-search-block .find-results-full-screen-mode__search')[0];
	    var advancedSearchItemsListBlock = window.document.querySelectorAll('.wpo-find-product-search-block > .multiselect > .multiselect__content-wrapper > .multiselect__content')[0];
	    var advancedSearchButtonsBlock = window.document.querySelectorAll('.wpo-find-product-search-block .find-results-full-screen-mode__actions')[0];
	    advancedSearchPopup.insertBefore(advancedSearchInputBlock, advancedSearchItemsListBlock);
	    advancedSearchPopup.appendChild(advancedSearchButtonsBlock);

	    window.document.getElementById('ajax-search-product').addEventListener('keydown', (e) => {
		if (e.keyCode === 13) {
		    this.onEnter(e);
		}
	    });
        },
        props: {
            title: {
                default: function() {
                    return '<h2 style="float: left; margin: 12px 12px 0 12px;"><span>Order details</span></h2>';
                }
            },
            addProductButtonTitle: {
                default: function() {
                    return 'Create custom product';
                }
            },
            addProductFromShopTitle: {
                default: function() {
                    return 'Browse shop and add products to cart';
                }
            },
            findProductsSelectPlaceholder: {
                default: function() {
                    return 'Find products...';
                }
            },
            findProductsSelectButtonAddToOrderLabel: {
                default: function() {
                    return 'Add to Order';
                }
            },
            findProductsSelectButtonConfigureLabel: {
                default: function() {
                    return 'Configure product';
                }
            },
            productsTableItemColumnTitle: {
                default: function() {
                    return 'Item';
                }
            },
            productsTableCostColumnTitle: {
                default: function() {
                    return 'Cost';
                }
            },
            productsTableQtyColumnTitle: {
                default: function() {
                    return 'Qty';
                }
            },
            productsTableTotalColumnTitle: {
                default: function() {
                    return 'Total';
                }
            },
            customerProvidedNoteLabel: {
                default: function() {
                    return 'Customer provided note';
                }
            },
            customerProvidedNotePlaceholder: {
                default: function() {
                    return 'Add a note';
                }
            },
            customerPrivateNoteLabel: {
                default: function() {
                    return 'Private note';
                }
            },
            customerPrivateNotePlaceholder: {
                default: function() {
                    return 'Add a note';
                }
            },
            subtotalLabel: {
                default: function() {
                    return 'Subtotal';
                }
            },
            addCouponLabel: {
                default: function() {
                    return 'Add coupon';
                }
            },
            feeNameLabel: {
                default: function() {
                    return 'Fee';
                }
            },
            addDiscountLabel: {
                default: function() {
                    return 'Add discount';
                }
            },
            manualDiscountLabel: {
                default: function() {
                    return 'Manual Discount';
                }
            },
            discountLabel: {
                default: function() {
                    return 'Discount';
                }
            },
            addShippingLabel: {
                default: function() {
                    return 'Add shipping';
                }
            },
            shippingLabel: {
                default: function() {
                    return 'Shipping';
                }
            },
            recalculateButtonLabel: {
                default: function() {
                    return 'Recalculate';
                }
            },
            taxLabel: {
                default: function() {
                    return 'Taxes';
                }
            },
            orderTotalLabel: {
                default: function() {
                    return 'Order Total';
                }
            },
            createOrderButtonLabel: {
                default: function() {
                    return 'Create order';
                }
            },
            viewOrderButtonLabel: {
                default: function() {
                    return 'View order';
                }
            },
            sendOrderButtonLabel: {
                default: function() {
                    return 'Send invoice';
                }
            },
            createNewOrderLabel: {
                default: function() {
                    return 'Create new order';
                }
            },
            payOrderNeedProVersionMessage: {
                default: function() {
                    return 'Want to pay order as customer?';
                }
            },
            buyProVersionMessage: {
                default: function() {
                    return 'Buy Pro version';
                }
            },
            tabName: {
                default: function() {
                    return 'add-order';
                }
            },
            isProVersion: {
                default: function() {
                    return false;
                }
            },
            logRowID: {
                default: function() {
                    return '';
                }
            },
            productItemLabels: {
                default: function() {
                    return {};
                }
            },
            noResultLabel: {
                default: function() {
                    return 'Oops! No elements found. Consider changing the search query.';
                }
            },
            couponsEnabled: {
                default: function() {
                    return false;
                }
            },
            activateCouponsLabel: {
                default: function() {
                    return "Please, enable coupons to use discounts.";
                }
            },
            chooseMissingAttributeLabel: {
                default: function() {
                    return "Please, choose all attributes.";
                }
            },
	        duplicateOrderLabel: {
		        default: function() {
			        return 'Duplicate order';
		        }
	        },
            copyCartButtonLabel: {
                default: function() {
                    return null;
                }
            },
            copyCopiedCartButtonLabel: {
                default: function() {
                    return null;
                }
            },
            fillAllFieldsLabel: {
                default: function() {
                    return 'Please fill out all required fields!';
                }
            },
            multiSelectSearchDelay: {
                default: function() {
                    return 1000;
                }
            },
            noOptionsTitle: {
                default: function() {
                    return 'List is empty.';
                }
            },
            disableProductSearch: {
                default: function() {
                    return false;
                }
            },
            useConfigureProductActionAsDefault: {
                default: function() {
                    return false;
                }
            },
            clearCartAfterCreateOrder: {
                default: function() {
                    return false;
                }
            },
            multipleSelectedProductsCountLabel: {
                default: function() {
                    return 'Selected';
                }
            },
            addMultipleSelectedProductsLabel: {
                default: function() {
                    return 'Add to cart';
                }
            },
            cancelMultipleSelectedProductsLabel: {
                default: function() {
                    return 'Cancel';
                }
            },
            browseProductsMultipleSelectedProductsLabel: {
                default: function() {
                    return 'Advanced search';
                }
            },
	    openViewOrderInSameTab: {
                default: function() {
                    return false;
                }
            },
            packageLabel: {
                default: function() {
                    return 'Package';
                }
            },
	    itemsLabel: {
		    default: function() {
			    return 'items';
		    }
	    },
	    itemLabel: {
		    default: function() {
			    return 'item';
		    }
	    },
	    removeLabel: {
		    default: function() {
			    return 'Remove';
		    }
	    },
	    addLabel: {
		    default: function() {
			    return 'Add';
		    }
	    },
            shippingGrantedByCoupon: {
                default: function() {
                    return 'granted by coupon';
                }
            },
            copyCheckoutLinkTitle: {
                default: function() {
                    return 'Copy to clipboard';
                }
            },
            columnDiscountTitle: {
                default: function() {
                    return 'Discount';
                }
            },
            barcodeModeAlertMessage: {
                default: function() {
                    return 'Barcode mode is enabled! "Find products" working only after click Enter key';
                }
            },
            productSubscriptionOptions: {
                default: function() {
                    return {};
                }
            },

        },
        data: function () {
            return {
                product: null,
                isLoading: false,
                selectedItems: [],
                subtotal: 0,
                subtotalWithTax: 0,
                totalDiscount: 0,
                totalTax: 0,
                orderTotal: 0,
                orderTotalWithTax: 0,
                discountAmount: 0,
                calculatedItems: [],
                lastRequestTimeoutID: null,
                taxTotals: {},
		defaultFindProducts: [],
		defaultFindProductsList: [],

                // for error message
                errorMessage: '',
                dismissCountDown: 0,
                dismissSecs: 3,
                additionalData: [],

                multipleSelectedProducts: {},
		findProductsList: [],

		productItemsEditableCustomMetaFields: {},

		searchMultipleSelectedProducts: '',
		showSearchMultipleSelectedProducts: false,
		isOpenSearchMultipleSelectedProducts: false,
		searchMultipleSelectedProductsList: [],

		searchProduct: '',
		searchProductList: [],

		checkoutLink: null,
		disableLoadDefaultFindProducts: true,

		isValidCartErrorMessage: this.fillAllFieldsLabel,
            };
        },
        watch: {
            additionalProductSearchParams (newVal, oldVal) {

                if (JSON.stringify(newVal) === JSON.stringify({}) && this.searchMultipleSelectedProducts === '') {
		    if (oldVal && JSON.stringify(oldVal) !== JSON.stringify({})) {
			this.autoDeactivateSelectSearchProduct();
		    }
                    return;
                }

                this.autoSelectSearchProduct();
            },
            customer (newVal, oldVal) {
		 !this.autoRecalculate && ! this.deepIsEqual(newVal, oldVal, ['custom_fields']) && this.recalculate();

		if (this.getSettingsOption('switch_customer_while_calc_cart') && newVal.id != oldVal.id) {
		    this.loadFindProducts(this.defaultFindProductsIDs);
		}
            },
	        // shipping (newVal, oldVal) {
		    // !this.autoRecalculate && null === newVal && this.recalculate();
	        // },
            productsList(newVal, oldVal) {

		this.multipleSelectedProducts = {};

		newVal.forEach((product) => {
		    this.$set(this.multipleSelectedProducts, product.value, {
			checked: false,
			product: product,
		    });
		});
            },
	    defaultFindProductsIDs(newVal) {
		this.loadFindProducts(newVal);
	    },
	    searchMultipleSelectedProducts(newVal) {
		if (this.isOpenSearchMultipleSelectedProducts) {
		    this.asyncFind(newVal, this.isExistsAdditionalProductSearchParams);
		}
	    },
	    showSearchMultipleSelectedProducts(newVal) {
		if (newVal) {
		    this.openSelectSearchProductPopup();
		} else {
		    this.closeSelectSearchProductPopup();
		}
	    },
	    isOpenSearchMultipleSelectedProducts(newVal) {
		if (newVal) {
		    document.getElementsByTagName('body')[0].classList.add('wpo-search-product-list-full-screen-mode');
		} else {
		    document.getElementsByTagName('body')[0].classList.remove('wpo-search-product-list-full-screen-mode');
		}
	    },
	    findProductsList (newVal) {
		this.$nextTick(() => {
		    if ((this.$refs.productSelectSearch.search !== '' || this.isExistsAdditionalProductSearchParams) && !newVal.length) {
			this.$refs.productSearchNoResult.parentElement.parentElement.setAttribute('style', 'display: inline-block;')
		    } else {
			this.$refs.productSearchNoResult.parentElement.parentElement.setAttribute('style', 'display: none;')
		    }
		})
	    },
	    searchMultipleSelectedProductsList (newVal) {

		if (!this.isOpenSearchMultipleSelectedProducts) {
		    return;
		}

		this.$nextTick(() => {
		    if ((this.searchMultipleSelectedProducts !== '' || this.isExistsAdditionalProductSearchParams) && !newVal.length) {
			this.$refs.productSearchNoResult.parentElement.parentElement.setAttribute('style', 'display: inline-block;')
		    } else {
			this.$refs.productSearchNoResult.parentElement.parentElement.setAttribute('style', 'display: none;')
		    }
		});
	    },
        },
        computed: {
	    isAllowConfigureProduct() {
		return this.isFrontend && this.getSettingsOption('allow_to_configure_product');
        },
        isAllowAddProductsFromShopPage() {
        return this.isFrontend && this.getSettingsOption('allow_to_add_products_from_shop_page');
        },
	    isFrontend() {
		return typeof window.wpo_frontend !== 'undefined';
	    },
            showHeader () {
                return ! this.getSettingsOption('search_by_cat_and_tag');
            },
            buttonsMessage: {
                get () {
                    return this.$store.state.add_order.buttons_message;
                },
                set (newVal) {
                    this.$store.commit('add_order/setButtonsMessage', newVal)
                },
            },
            cart () {
                return this.$store.state.add_order.cart;
            },
            productSelectOptionsLimit: function () {
                return this.getSettingsOption('number_of_products_to_show');
            },
            productSelectCloseOnSelected: function () {
                return ! this.getSettingsOption('repeat_search');
            },
            productList: function () {
                return this.$store.state.add_order.cart.items;
            },
            feeList: function () {
                return this.$store.state.add_order.cart.fee;
            },
            couponList: function () {
                return this.$store.state.add_order.cart.coupons;
            },
            discount: function () {
                return this.$store.state.add_order.cart.discount;
            },
            customer: function () {
                return this.$store.state.add_order.cart.customer;
            },
            additionalProductSearchParams: {
		get() {
		    return this.$store.state.add_order.additional_params_product_search;
		},
		set(newVal) {
		    this.$store.commit('add_order/setAdditionalParamsProductSearch', newVal);
		}
            },
            customerProvidedNote: {
                get () {
                    return this.$store.state.add_order.cart.customer_note;
                },
                set (newVal) {
                    this.$store.commit('add_order/setCustomerNote', newVal);
                },
            },
            customerPrivateNote: {
                get () {
                    return this.$store.state.add_order.cart.private_note;
                },
                set (newVal) {
                    this.$store.commit('add_order/setPrivateNote', newVal);
                },
            },
            showCreateOrderButton () {
                return !!! this.$store.state.add_order.cart.order_id
                    && !! this.$store.state.add_order.cart.items.length
                    && !!! this.$store.state.add_order.cart.edit_order_id;
            },
            showViewOrderButton () {
                return !! this.$store.state.add_order.cart.order_id;
            },
            showSendOrderButton () {
                return !! this.$store.state.add_order.cart.order_id
		    && !! this.$store.state.add_order.cart.allow_refund_order
                    && !! this.customer.billing_email;
            },
            showCreateNewOrderButton () {
                return !! this.$store.state.add_order.cart.order_id;
            },
            showOrderActions () {
                return this.showCreateOrderButton
                    || !! this.$store.state.add_order.cart.edit_order_id
                    || this.showCreateOrderButton && ! this.$store.state.add_order.cart.edit_order_id
                    || !! this.$store.state.add_order.cart.order_id
                    || this.showViewOrderButton
                    || !! this.$store.state.add_order.cart.order_id
                    || this.showSendOrderButton
                    || this.showCreateNewOrderButton;
            },
            shipping () {
                return this.$store.state.add_order.cart.shipping;
            },
            hideAddDiscount () {
                return this.getSettingsOption('hide_add_discount');
            },
            cacheProductsSessionKey () {
                return this.getSettingsOption('cache_products_session_key');
            },
            autoRecalculate () {
                return this.getSettingsOption('auto_recalculate');
            },
            allowAddProducts () {
                return ! this.getSettingsOption('disable_adding_products');
            },
            allowDuplicateProducts() {
	            return this.getSettingsOption('allow_duplicate_products');
            },
            showCreateOrderButtonOption() {
	            return !!!this.getSettingsOption('hide_button_create_order');
            },
            scrollableCartContentsOption() {
                return this.getSettingsOption('scrollable_cart_contents');
            },
            excludeIDs () {

		if (this.isBarcodeMode) {
		    return [];
		}

                return this.allowDuplicateProducts ? [] : [...this.$store.state.add_order.cart.items.filter(function (product) {
		    return typeof product.wpo_readonly_child_item === 'undefined' || ! product.wpo_readonly_child_item;
		}).map(function (product) {
                    return product.variation_id || product.product_id;
                }), ...this.selectedItems];
            },
	    showDuplicateOrder() {
		    return !! this.$store.state.add_order.cart.order_id && this.getSettingsOption('show_duplicate_order_button');
	    },
	    hideCouponWarning() {
		return this.getSettingsOption('hide_coupon_warning');
	    },
	    hideAddShipping() {
		return this.getSettingsOption('hide_add_shipping');
	    },
	    showTaxTotalsOption() {
		return this.getSettingsOption('show_tax_totals');
	    },
	    showProductsTableExtraColumn() {
		return this.getSettingsOption('show_additional_product_column', false);
	    },
	    productsTableExtraColumnTitle() {
		return this.getSettingsOption('additional_product_column_title', '');
	    },
	    hideAddCoupon() {
		return this.getSettingsOption('hide_add_coupon');
	    },
	    defaultFindProductsIDs() {
		return this.getSettingsOption('item_default_search_result', []);
	    },
	    displayProductsFindResultsAsGrid() {
		return this.getSettingsOption('display_search_result_as_grid');
	    },
	    isVatExempt() {
		return this.$store.state.add_order.cart.customer ? this.$store.state.add_order.cart.customer.is_vat_exempt : null;
	    },
	    currencySymbol() {
		return this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
	    },
	    showTotalTax() {

		if (this.isVatExempt && this.getSettingsOption('hide_taxes_if_tax_exempt')) {
		    return false;
		}

		if (this.showTaxTotalsOption && Object.keys(this.taxTotals).length === 1) {
		    return false;
		}

		return true;
	    },
	    multipleSelectedProductsCount() {

		var count = 0;

		for(var product_id in this.multipleSelectedProducts) {
		    if (this.multipleSelectedProducts[product_id].checked) {
			count++;
		    }
		}

		return count;
	    },
	    productsList() {

		var products = this.showSearchMultipleSelectedProducts ? (this.searchMultipleSelectedProducts !== '' || this.isExistsAdditionalProductSearchParams ? this.searchMultipleSelectedProductsList : []) : (this.$refs.productSelectSearch && this.$refs.productSelectSearch.search !== '' || this.isExistsAdditionalProductSearchParams ? this.findProductsList : this.defaultFindProducts);

		products = products.filter((v) => {
		    return this.excludeIDs.indexOf(+v.value) === -1;
		});

		return products;
	    },
	    defaultActionClickOnTitleProductItemInSearchProducts() {
		return this.getSettingsOption('action_click_on_title_product_item_in_search_products', 'add_product_to_cart');
	    },
	    showProductSearchOptions() {
		return this.getSettingsOption('search_by_cat_and_tag');
	    },
	    isExistsAdditionalProductSearchParams() {
		return !!Object.keys(this.additionalProductSearchParams).length;
	    },
	    showPrivateNote() {
		return ! this.getSettingsOption('hide_private_note');
	    },
	    isBarcodeMode() {
		return this.getSettingsOption('barcode_mode');
	    },
	    showColumnDiscount() {
		return this.getSettingsOption('show_column_discount');
	    },
        },
        methods: {
            asyncFind(query, isEmptySearch, runSearch) {

		if (this.disableProductSearch) {
		    return;
		}

                this.lastRequestTimeoutID && clearTimeout(this.lastRequestTimeoutID);

		if (this.$refs.productSelectSearch.isOpen) {
		    this.searchMultipleSelectedProducts = query;
		}

                if (!query && !isEmptySearch) {
                    this.isLoading = false;
                    this.lastRequestTimeoutID = null;

		    if (this.showSearchMultipleSelectedProducts) {
			this.searchMultipleSelectedProductsList = [];
		    } else {
			this.findProductsList = [];
		    }

                    return;
                }

		this.isLoading = true;

		var requestSearchProduct = () => {

		    console.log(query)

                    this.axios.post(this.url, this.qs.stringify({
			action: 'phone-orders-for-woocommerce',
			method: 'search_products_and_variations',
			tab: this.tabName,
			term: query,
			exclude: JSON.stringify(this.excludeIDs),
			additional_parameters: this.additionalProductSearchParams,
			wpo_cache_products_key: this.cacheProductsSessionKey,
			customer_id: this.customer.id,
			cart: JSON.stringify(this.$store.state.add_order.cart),
                    })).then((response) => {

                        var products = [];

                        for (var id in response.data) {
                            if (response.data.hasOwnProperty(id)) {
                                var product_id = response.data[id].product_id;
				products.push({
				    title: response.data[id].title,
				    value: product_id,
				    img: response.data[id].img,
				    permalink: response.data[id].permalink,
				    product_link: response.data[id].product_link,
				    configure_product_page_link: response.data[id].configure_product_page_link,
				    add_to_exclude: response.data[id].add_to_exclude,
				    query: query,
				});
			    }
			}

			if (this.showSearchMultipleSelectedProducts) {
			    this.searchMultipleSelectedProductsList = products;
			} else {
			    this.findProductsList = products;
			    this.searchMultipleSelectedProductsList = products;
			}

                        this.isLoading = false;

			if (this.isBarcodeMode && products.length < 2) {
			    this.addProductItemsToCart(products);
			}
                    });
                }

		if (this.isBarcodeMode) {

		    if (runSearch) {

			var cartItems = JSON.parse(JSON.stringify(this.$store.state.add_order.cart.items));

			var found = false;

			cartItems.forEach((item) => {
			    if (typeof item.barcode !== 'undefined' && item.barcode === query) {
				item.qty++;
				found = true;
			    }
			});

			if (found) {
			    this.calculatedItems = cartItems;
			    this.$store.commit('add_order/setCartItems', cartItems);
			    this.$nextTick(() => {
				this.$refs.productSelectSearch.search = '';
				this.$refs.productSelectSearch.activate();
			    });
			} else {
			    requestSearchProduct();
			}
		    }
		} else {
		    this.lastRequestTimeoutID = setTimeout(requestSearchProduct, this.multiSelectSearchDelay);
		}
            },
            addCoupon () {
		this.openModal('addCoupon');
            },
            addDiscount () {
                this.openModal('addDiscountModal');
            },
            addShipping (hash) {
				this.$root.bus.$emit('edit-shipping-package', hash);
                // this.openModal('shippingModal');
            },
	    getShippingForPackage(hash) {
            	var shipping = {};

		this.shipping.packages.forEach(function(item){
			if ( hash === item.hash ) {
				shipping = item;
			}
		});

		return shipping;
	    },
            addCustomProductItem () {
                this.openModal('addCustomItemModal');
            },
	        isAllAttributesSelected() {
            	var all_selected = true;

		        this.$store.state.add_order.cart.items.forEach(function(item){
		        	if ( typeof item.missing_variation_attributes === 'object' ) {
				        item.missing_variation_attributes.forEach(function(attribute){
					        if ( typeof attribute.value !== 'undefined' && ! attribute.value ) {
						        all_selected = false;
					        }
				        });
                    }
                });

                return all_selected;
            },
            isCartValid() {

                var valid = true;

		this.isValidCartErrorMessage = this.fillAllFieldsLabel;

                this.$children.forEach((child) => {
                    if (typeof child.checkCart !== 'undefined') {
                        if (!!!child.checkCart()) {
			    valid = false;
			    if (typeof child.getCheckCartMessage !== 'undefined' && !!child.getCheckCartMessage()) {
				this.isValidCartErrorMessage = child.getCheckCartMessage();
			    }
                            return;
                        }
                    }
                });

                this.$parent.$children.forEach((child) => {
                    if (typeof child.checkCart !== 'undefined') {
                        if (!!!child.checkCart()) {
                            valid = false;
			    if (typeof child.getCheckCartMessage !== 'undefined' && !!child.getCheckCartMessage()) {
				this.isValidCartErrorMessage = child.getCheckCartMessage();
			    }
                            return;
                        }
                    }
                });

                return valid;
            },
            onCreateOrder () {
	            if ( ! this.isAllAttributesSelected() ) {
		            alert( this.chooseMissingAttributeLabel );
		            return;
	            }

	            if ( ! this.isCartValid() ) {
	                this.showAlert();
                    this.errorMessage = this.isValidCartErrorMessage;
                    return;
                }

                if ( ! this.$store.state.add_order.cart.drafted_order_id ) {
                    this.createOrder();
                }

                this.$root.bus.$emit('create-order');
            },
            onCreateOrderCheckError() {

            },
            createOrder () {

                var cart = this.$store.state.add_order.cart;

                if (cart.customer && typeof cart.customer.set_by_default !== 'undefined') {
                    delete cart.customer.set_by_default;
                }

                this.$store.commit('add_order/setIsLoading', true);

                this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'create_order',
                    cart: JSON.stringify(this.$store.state.add_order.cart),
                    created_date_time: this.$store.state.add_order.order_date_timestamp,
                    order_status: this.$store.state.add_order.order_status,
                    tab: this.tabName,
                    log_row_id: this.$store.state.add_order.log_row_id,
                })).then( ( response ) => {
		    if ( !!! response.data.success ) {
                		this.showAlert();
						this.errorMessage = response.data.data;
						this.$store.commit('add_order/setCartEnabled', true);
						this.$store.commit('add_order/setIsLoading', false);
						return;
					}

                    this.$store.commit('add_order/setCartOrderID', response.data.data.order_id);
                    this.$store.commit('add_order/setCartOrderNumber', response.data.data.order_number);
                    this.$store.commit('add_order/setCartOrderIsCompleted', response.data.data.is_completed);
                    this.$store.commit('add_order/setCartOrderPaymentUrl', response.data.data.payment_url);
                    this.$store.commit('add_order/setCartAllowRefundOrder', response.data.data.allow_refund_order);
                    this.$store.commit('add_order/setCartEnabled', false);
                    this.buttonsMessage = response.data.data.message;
                    this.$store.commit('add_order/setIsLoading', false);
                    this.updateStoredCartHash();

		    if (this.clearCartAfterCreateOrder) {
			setTimeout(() => {
			    this.createNewOrder();
			}, 2000);
		    }
                });

            },
            viewOrder () {

                var link = this.base_admin_url + "post.php?post=" + this.$store.state.add_order.cart.order_id + "&action=edit";

		if (this.openViewOrderInSameTab) {
		    window.location.href = link;
		} else {
		    window.open( link );
		}
            },
            sendOrder () {
                this.$store.commit('add_order/setIsLoading', true);
                this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'create_order_email_invoice',
                    order_id: this.$store.state.add_order.cart.order_id,
                    tab: this.tabName,
                })).then( ( response ) => {
                    this.buttonsMessage = response.data.data.message;
                    this.$store.commit('add_order/setIsLoading', false);
                });
            },
            createNewOrder () {

                this.$root.bus.$emit('create-new-order', {callback: () => {
                    this.axios.get(this.url, { params: {
                            action: 'phone-orders-for-woocommerce',
                            method: 'generate_log_row_id',
                            tab: this.tabName,
                    }}).then( ( response ) => {
                            this.$store.commit('add_order/setLogRowID', response.data.data.log_row_id);
                    }, () => {});
                }});
            },
            addProductItemsToCart (products) {

		var items = [];

		var cartItems = JSON.parse(JSON.stringify(this.$store.state.add_order.cart.items));

		products.forEach((product) => {

		    if (this.isBarcodeMode) {

			var found = false;

			cartItems.forEach((item) => {
			    if (+(item.variation_id || item.product_id) === +product.value) {
				item.qty++;
				item.barcode = product.query;
				found = true;
			    }
			});

			if (!found) {
			    items.push(product.value);
			}
		    } else {
			if ( this.selectedItems.indexOf( + product.value ) === -1 || this.allowDuplicateProducts ) {
			    items.push(product.value);
			}

			if ( this.selectedItems.indexOf( + product.value ) === -1 && product.add_to_exclude ) {
			    this.selectedItems.push( + product.value );
			}
		    }
		});

		if (this.isBarcodeMode) {
		    this.calculatedItems = cartItems;
		    this.$store.commit('add_order/setCartItems', cartItems);
		    if (!items.length) {
			this.product = null;
			this.$nextTick(() => {
			    this.$refs.productSelectSearch.search = '';
			    this.$refs.productSelectSearch.activate();
			});
		    }
		}

		if (!items.length) {
		    return false;
		}

                this.isLoading = true;

                this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'load_items',
                    ids: items,
		    cart: JSON.stringify(this.$store.state.add_order.cart),
                    tab: this.tabName,
		    qty: 1,
                    customer_id: this.customer.id,
                })).then( ( response ) => {

		    if (this.isBarcodeMode) {
			response.data.data.items.forEach((item) => {
			    products.forEach((product) => {
				if (+(item.variation_id || item.product_id) === +product.value) {
				    item.barcode = product.query;
				}
			    });
			});
		    }

                    this.addProductItemsToStore(response.data.data.items);
//                    this.selectedItems = this.selectedItems.filter((item) => {
//                        return item !== +product.value;
//                    });
                    this.isLoading = false;
                    this.product   = null;

		    if (this.isBarcodeMode) {
			this.$nextTick(() => {
			    this.$refs.productSelectSearch.search = '';
			    this.$refs.productSelectSearch.activate();
			});
			return;
		    }

                    this.asyncFind(this.$refs.productSelectSearch.search);
                    this.productSelectCloseOnSelected && response.data.data.items.length && this.setFocusToItemQty(response.data.data.items[0]);
                }, () => {
                    this.isLoading = false;
                });
            },
	        setFocusToItemQty( item ) {
		        if ( this.isQtyChangeAvailible( item ) ) {
			        this.$nextTick( () => {
				        this.$refs[this.getProductRef( item )][0].$refs['qty'].focus();
			        } );
		        }
	        },
            isQtyChangeAvailible(item) {
            	return ! item.sold_individually;
            },
            openSelectSearchProduct () {
		this.showSearchMultipleSelectedProducts	    = true;
		this.isOpenSearchMultipleSelectedProducts   = true;
            },
            openSelectSearchProductPopup () {

		this.$nextTick(() => {
		    this.$refs.productSelectSearch.optimizedHeight = window.innerHeight - 100;
		    this.$refs.productSelectSearch.$refs.list.style.top = "" + (50 - this.$refs.productSelectSearch.$el.getBoundingClientRect().top) + "px";
		    this.$refs.productSelectSearch.$refs.list.style.height = this.$refs.productSelectSearch.optimizedHeight + "px";

		    this.$refs.productSelectSearch.$refs.list.style.width = document.getElementById('woo-phone-orders').offsetWidth + "px";
		    this.$refs.productSelectSearch.$refs.list.style.left = "-12px";

		    this.$refs.productSelectSearch.$refs.search.blur();
		    this.$refs.productSelectSearch.isOpen = true;

		    this.$nextTick(() => {
			this.$refs.searchMultipleSelectedProducts.focus();
		    })
		})
            },
            closeSelectSearchProductPopup () {

		this.$nextTick(() => {
		    this.$refs.productSelectSearch.$refs.list.style.top = '';
		    this.$refs.productSelectSearch.$refs.list.style.height = '';
		    this.$refs.productSelectSearch.$refs.list.style.width = '';
		    this.$refs.productSelectSearch.$refs.list.style.left = '';
		    this.$refs.productSelectSearch.$refs.list.children[0].style.height = '';
		})
            },
            removeFee (fee, index) {
                this.$store.commit('add_order/removeFeeItem', index);
            },
            removeCoupon (coupon, index) {
                this.$store.commit('add_order/removeCouponItem', index);
            },
            autoSelectSearchProduct () {

		this.asyncFind(this.searchMultipleSelectedProducts, this.isExistsAdditionalProductSearchParams);

		if (!this.isOpenSearchMultipleSelectedProducts) {
		    this.$refs.productSelectSearch.activate();
		}
            },
            autoDeactivateSelectSearchProduct () {
		if (!this.isOpenSearchMultipleSelectedProducts) {
		    this.$refs.productSelectSearch.deactivate();
		}
            },
            openProductSearchSelect () {
                this.$refs.productSelectSearch.activate();
            },
            recalculate () {

                this.$store.commit('add_order/setIsLoading', true);

                this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'recalculate',
                    cart: JSON.stringify(this.$store.state.add_order.cart),
                    tab: this.tabName,
                    log_row_id: this.$store.state.add_order.log_row_id,
		    is_frontend: this.isFrontend ? 1 : 0,
                })).then( ( response ) => {

                    this.recalculateCallback(response.data.data);

                    this.$store.commit('add_order/setIsLoading', false);
                    this.$store.commit('add_order/setIsLoadingWithoutBackground', false);
                }, () => {
                    this.$store.commit('add_order/setIsLoading', false);
                    this.$store.commit('add_order/setIsLoadingWithoutBackground', false);
                });

            },
            recalculateCallback (cart) {

		if (cart) {

		    this.subtotal           = cart.subtotal;
		    this.subtotalWithTax    = cart.subtotal_with_tax;
		    this.totalDiscount      = cart.discount;
		    this.totalTax           = cart.taxes;
		    this.orderTotal         = cart.total_ex_tax;
		    this.orderTotalWithTax  = cart.total;
		    this.discountAmount     = cart.discount_amount;
		    this.calculatedItems    = cart.items;
		    this.taxTotals          = cart.tax_totals;
		    this.additionalData     = cart.additional_data;

		    this.$store.commit('add_order/setCartParamsChangedByBackend', 1);
		    this.$store.commit('add_order/setShipping', cart.shipping);
		    this.$store.commit('add_order/setCartCoupons', cart.applied_coupons);
		    this.$store.commit('add_order/setCartFees', cart.applied_fees);
		    this.$store.commit('add_order/setCalculatedDeletedItems', cart.deleted_items);
		    this.$store.commit('add_order/updateAvailablePaymentGateways', cart.payment_gateways);
		    this.$store.commit('add_order/setPriceSettings', cart.wc_price_settings);
		    this.$store.commit('add_order/setTaxSettings', cart.wc_tax_settings);
		    this.$store.commit('add_order/setMeasurementsSettings', cart.wc_measurements_settings);

		    let excludeIDs = cart.deleted_items.map(function (item) {
			return +item.key;
		    });
		    let calculatedKeys = this.calculatedItems.map(function (item) {
			return +item.key;
		    });

		    let productList = this.productList.filter(function (item) {
			return excludeIDs.indexOf(+item.key) === -1 && calculatedKeys.indexOf(+item.key) !== -1;
		    });

		    let productKeys = productList.map(function (item) {
			    return +item.key;
		    });
			productList = [];
			this.calculatedItems.forEach( function ( item, index ) {
				productList.push( item.loaded_product );
			} );

			var productItemsEditableCustomMetaFields = {};

			this.calculatedItems.forEach( ( item, index ) => {
			    if (typeof this.productItemsEditableCustomMetaFields[item.wpo_key] !== 'undefined') {
				productItemsEditableCustomMetaFields[item.key] = this.productItemsEditableCustomMetaFields[item.wpo_key];
			    }
			} );

			this.productItemsEditableCustomMetaFields = productItemsEditableCustomMetaFields;

			this.$store.commit('add_order/setForceCartSet', 1);
			this.$store.commit('add_order/setCartItems', productList);

		    this.$store.commit('add_order/updatePaymentMethod', cart.payment_method);
		    this.$store.commit('add_order/updateAvailablePaymentGateways', cart.payment_gateways);

		} else {
		    this.subtotal           = 0;
		    this.subtotalWithTax    = 0;
		    this.totalDiscount      = 0;
		    this.totalTax           = 0;
		    this.orderTotal         = 0;
		    this.orderTotalWithTax  = 0;
		    this.discountAmount     = 0;
		    this.calculatedItems    = [];
		    this.$store.commit('add_order/setCalculatedDeletedItems', []);
		}

            },
            getProductKey (item) {
            	return item.key? item.key : (item.variation_id ? item.variation_id : item.product_id);
            },
            getProductRef (item) {
                return 'item_' + this.getProductKey(item);
            },
            getProductItemObject (item) {
	            let key = item.key ? 'key' : (item.variation_id ? 'variation_id' : 'product_id');
	            let value = item.key ? item.key : (item.variation_id ? item.variation_id : item.product_id);

	            return this.getObjectByKeyValue(this.calculatedItems, key,value, {});
            },
            loadDefaultSelectedItems () {
                this.loadItems(this.getSettingsOption('item_default_selected'));
            },
            initEmptyCart (successCallback, errorCallback) {

		this.$store.commit('add_order/setIsLoading', true);

		let user_id = this.removeGetParameter( "user_id" );

		var params = {
                    action: 'phone-orders-for-woocommerce',
                    method: 'init_order',
		    cart: JSON.stringify(this.$store.state.add_order.default_cart),
                    tab: this.tabName,
		};

		if (user_id !== null) {
		    params['customer_id'] = user_id;
		}

		this.axios.post(this.url, this.qs.stringify(params)).then( ( response ) => {

		    var cart = {
			customer: response.data.state.default_customer,
                        custom_fields_values: response.data.state.default_order_custom_field_values,
                    };

                    this.$store.commit(
                        'add_order/setStateToDefault',
                        {cart: cart}
                    );

                    /** Fix
                     Missing focus on product search input after initial load.
                     After commit to store 'add_order/setStateToDefault'
                     @see this.additionalProductSearchParams watcher is triggering.
                     On load additional_params_product_search is empty, so search input is disabled and losing focus.
                     To prevent it, we move setting focus from "mounted" event
                     */
                    this.$nextTick(() => {
			if (this.isBarcodeMode) {
			    this.openProductSearchSelect();
			}
			if (typeof params['customer_id'] === 'string'
			    && params['customer_id'].toLowerCase() === 'new'
			) {
			    this.openModal('addCustomer');
			}
                    });

                    this.setDefaultCustomFieldsValuesEx();

                    this.updateStoredCartHash();

                    this.$store.commit('add_order/updateOrderStatus', response.data.state.default_order_status);

		    this.$store.commit('add_order/setLogRowID', response.data.state.log_row_id);

                    // fix unavailable to use same product in new order (after create new order click)
                    this.selectedItems = [];

		    this.recalculateCallback(response.data.cart)

		    if (typeof successCallback === 'function') {
                        successCallback();
                    }

                    this.$store.commit('add_order/setIsLoading', false);
                }, () => {
		    if (typeof errorCallback === 'function') {
                        errorCallback();
                    }
                    this.$store.commit('add_order/setIsLoading', false);
                });
            },
	        duplicateOrder() {
		        this.$store.commit('add_order/purgeCartOrder');
		        this.$store.commit('add_order/setCartEnabled', true);
		        this.$store.commit('add_order/setButtonsMessage', "")
	        },
            countDownChanged(dismissCountDown) {
                this.dismissCountDown = dismissCountDown
            },
            showAlert() {
                this.dismissCountDown = this.dismissSecs
            },
	    addProductToOrder() {
		this.addProductItemsToCart([this.product]);
	    },
	    configureProduct(product) {
		this.$root.bus.$emit('configure-product-open', product);
	    },
            addConfiguredProduct () {

		this.product = null;

                this.$store.commit('add_order/setIsLoading', true);

                this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'add_configured_products',
                    cart: JSON.stringify(this.$store.state.add_order.cart),
                    tab: this.tabName,
                    log_row_id: this.$store.state.add_order.log_row_id,
                })).then( ( response ) => {

		    this.recalculateCallback(response.data.data);

                    this.$store.commit('add_order/setIsLoading', false);
                    this.$store.commit('add_order/setIsLoadingWithoutBackground', false);
                }, () => {
                    this.$store.commit('add_order/setIsLoading', false);
                    this.$store.commit('add_order/setIsLoadingWithoutBackground', false);
                });

            },
            loadFindProducts (ids) {

		if (this.disableLoadDefaultFindProducts) {
		    return;
		}

		if (this.isBarcodeMode) {
		    return;
		}

		if (!ids || !ids.length) {
		    this.defaultFindProducts = [];
		    return;
		}

                this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'load_find_products',
                    ids: ids,
		    customer_id: this.customer.id,
		    cart: JSON.stringify(this.$store.state.add_order.cart),
                    tab: this.tabName,
                    log_row_id: this.$store.state.add_order.log_row_id,
                })).then( ( response ) => {

		    var products = [];

		    var data = response.data.data;

		    for (var id in data) {
			if (data.hasOwnProperty(id)) {
			    var product_id = data[id].product_id;
			    products.push({
				title: data[id].title,
				value: product_id,
				img: data[id].img,
				permalink: data[id].permalink,
				product_link: data[id].product_link,
				configure_product_page_link: data[id].configure_product_page_link,
			    });
			}
		    }

		    this.defaultFindProducts = products;

                }, () => {});

            },
	    manualRecalculate() {

		var subtotal	    = 0;
		var subtotalWithTax = 0;

		this.productList.forEach((product) => {
		    subtotal	    += parseFloat(product.item_cost) * product.qty;
		    subtotalWithTax += (product.item_cost_with_cost ? parseFloat(product.item_cost_with_cost) : parseFloat(product.item_cost)) * product.qty;
		});

		this.subtotal	     = subtotal;
		this.subtotalWithTax = subtotalWithTax;
	    },
	    calculateShippingLabel(shippingPackage) {

            	let qty = 0;
		shippingPackage.contents.forEach((item) => {qty += parseFloat(item.quantity)});

		return qty + " " + (qty === 1 ? this.itemLabel : this.itemsLabel);
        },
        changeProductItemsOrder(change_order_data) {
            this.productList.splice(change_order_data.newIndex, 0, this.productList.splice(change_order_data.oldIndex, 1)[0]);
        },
	    updateEditableProductItemCustomMetaFields(data) {
		this.productItemsEditableCustomMetaFields[data.product_key] = data.editable;
	    },
	    getEditableProductItemCustomMetaFields(product) {
		return typeof this.productItemsEditableCustomMetaFields[product.key] !== 'undefined' ? this.productItemsEditableCustomMetaFields[product.key] : false;
	    },
	    addMultipleSelectedProducts() {

		var items = [];

		for(var product_id in this.multipleSelectedProducts) {
		    if (this.multipleSelectedProducts[product_id].checked) {
			items.push(this.multipleSelectedProducts[product_id].product);
		    }
		}

		this.addProductItemsToCart(items);
		this.cancelMultipleSelectedProducts();
	    },
	    cancelMultipleSelectedProducts() {

		for(var product_id in this.multipleSelectedProducts) {
		    this.multipleSelectedProducts[product_id].checked = false;
		}

		this.isOpenSearchMultipleSelectedProducts = false;

		if (!this.isExistsAdditionalProductSearchParams) {
		    this.searchMultipleSelectedProducts = '';
		    this.searchMultipleSelectedProductsList = [];
		}

		setTimeout(() => {
		    if (!this.isOpenSearchMultipleSelectedProducts) {
			this.showSearchMultipleSelectedProducts = false;
		    }
		}, 500)

		this.$refs.productSelectSearch.deactivate();
	    },
	    closeMultipleSelectedProducts() {
		if (!this.isExistsAdditionalProductSearchParams) {
		    this.searchMultipleSelectedProducts = '';
		    if (!this.$refs.productSelectSearch.search.length) {
			this.findProductsList = [];
		    }
		}

		if (this.isOpenSearchMultipleSelectedProducts) {
		    this.cancelMultipleSelectedProducts();
		}
	    },
	    updateProductSearch (query) {
		this.asyncFind(query, this.isExistsAdditionalProductSearchParams)
	    },
            getRateLabelGrantedByCoupon(method_label) {
                return method_label + " ("  + this.shippingGrantedByCoupon + ")";
            },
	    copyCheckoutLink() {
		this.$copyText(this.checkoutLink).then((e) => {
                    this.$refs.checkoutLinkInput.$el.select();
                }, (e) => {});
	    },
	    onEnter(e) {
		if (!this.findProductsList.length) {
		    this.asyncFind(e.target.value, false, true);
		}
		e.stopPropagation();
	    },
        },
        components: {
            Multiselect,
            ProductItem,
            CopyCartButton,
        FaIcon,
        draggable,
        },
    }
</script>
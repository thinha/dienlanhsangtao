<template>
    <div class="postbox disable-on-order">
        <h2>
            <span>{{ title }}</span>
        </h2>
        <div class="date-picker">
            <datepicker
                    v-model="orderDate"
                    @input="store"
                    v-bind:disabled="!cartEnabled"
                    :format="formatter"
		    :typeable="true"
            ></datepicker>
        </div>
        <div class="time-picker">
            <input type="number"
                   @blur="store"
                   class="hour"
                   :placeholder="hourPlaceholder"
                   v-model="orderTimeHour"
                   name="order_date_hour"
                   min="0"
                   max="23"
                   step="1"
                   pattern="([01]?[0-9]{1}|2[0-3]{1})"
                   v-bind:disabled="!cartEnabled"
            />
                :
            <input type="number"
                   @blur="store"
                   class="minute"
                   :placeholder="minutePlaceholder"
                   name="order_date_minute"
                   min="0"
                   max="59"
                   step="1"
                   v-model="orderTimeMinute"
                   pattern="[0-5]{1}[0-9]{1}"
                   v-bind:disabled="!cartEnabled"
            />
            <input type="hidden" name="order_date_second" v-model="orderTimeSecond" />
        </div>
        <br class="clear">
    </div>
</template>

<style>
    .postbox.disable-on-order .date-picker, .postbox.disable-on-order .time-picker {
        display: inline-block;
        margin: 5px;
    }
    .postbox.disable-on-order .date-picker input{
        width: 100%;
        text-align: center;
        height: 30px;
        padding: 0;
        font-size: 16px;
	background-color: #eee;
    }
    .postbox.disable-on-order .date-picker {
        float:left;
        width: 48%;
	max-width: 200px;

    }
    .postbox.disable-on-order .time-picker {
        float:right;
    }
    .postbox.disable-on-order .time-picker input{
        height: 30px;
        text-align: end;
        padding: 0;
        font-size: 16px;
        background-color: #eee;
	width: 52px;
    }

    @media (min-width:768px) and (max-width:1023px) {
	.postbox.disable-on-order .time-picker {
	    float:none;
	}
    }

</style>

<script>

	import Datepicker from 'vuejs-datepicker';
	import moment from 'moment'

	export default {
		props: {
			title: {
				default: function () {
					return 'Order date';
				}
			},
			currentDateTimeTimestamp: {
				default: function () {
					return 0;
				}
			},
			hourPlaceholder: {
				default: function () {
					return 'h';
				}
			},
			minutePlaceholder: {
				default: function () {
					return 'm';
				}
			},
			timeZoneOffset: {
				default: function () {
					return 0;
				}
			},
		},
		data: function () {
			return {
				orderDate: "",
				orderTimeHour: "",
				orderTimeMinute: "",
				orderTimeSecond: "",
			};
		},
		watch: {
			orderDateTime( newVal, oldVal ) {
				this.orderDate = newVal;
				this.orderTimeHour = newVal.getHours() >= 10 ? newVal.getHours().toString() : "0" + newVal.getHours();
				this.orderTimeMinute = newVal.getMinutes() >= 10 ? newVal.getMinutes().toString() : "0" + newVal.getMinutes();
				this.orderTimeSecond = newVal.getSeconds() >= 10 ? newVal.getSeconds().toString() : "0" + newVal.getSeconds();
			}
		},
		computed: {
			storedOrderDateTime: {
				get: function () {
					return this.$store.state.add_order.order_date_timestamp;
				},
				set: function ( newVal ) {
					this.$store.commit( 'add_order/updateOrderDateTimestamp', newVal );
				},
			},
			orderDateTime() {
				return this.calcTime( this.storedOrderDateTime );
			},
		},
		created: function () {
			this.storeDate( this.getCurrentDateTime() );
		},
		methods: {
			getCurrentDateTime() {
				return this.calcTime();
			},
			calcTime( timestamp = null ) {
				// create Date object for current location
				let d = new Date();

				if ( ! timestamp ) {
					timestamp = Math.floor( d.getTime() / 1000 );
				}
				// convert to msec
				// subtract local time zone offset
				// get UTC time in msec
				let utc = timestamp * 1000 + (
					d.getTimezoneOffset() * 60000
				);

				// create new Date object for different city
				// using supplied offset
				let nd = new Date( utc + (
					3600000 * this.timeZoneOffset
				) );

				return nd;
			},
			calcTimestamp( date = null ) {
				if ( ! date ) {
					date = new Date();
				}

				return Math.floor( (
					                   date.getTime() - date.getTimezoneOffset() * 60000 - 3600000 * this.timeZoneOffset
				                   ) / 1000 );
			},

			storeDate( date ) {
				this.storedOrderDateTime = this.calcTimestamp( date );
			},
			store( e ) {
				let newDate = this.orderDateTime;
				newDate.setFullYear( this.orderDate.getFullYear() );
				newDate.setMonth( this.orderDate.getMonth() );
				newDate.setDate( this.orderDate.getDate() );

				newDate.setHours( this.orderTimeHour );
				newDate.setMinutes( this.orderTimeMinute );
				newDate.setSeconds( this.orderTimeSecond );

				this.storeDate( newDate )
			},
			formatter(date) {
				return moment(date).format('YYYY-MM-DD');
			}
		},
		components: {
			Datepicker,
		},
	}
</script>
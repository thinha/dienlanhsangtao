import moment from 'moment'

export default {
    inserted: function (el, binding, vnode) {

        var inputDate = vnode.componentInstance.$children[0];
        var inputBlurred = inputDate.inputBlurred;
        var parseTypedDate = inputDate.parseTypedDate;
        var dateFormat = vnode.componentInstance.$parent._props.dateFormat;

        inputDate.parseTypedDate = function (event) {

            // close calendar if escape or enter are pressed
            if ([
                27, // escape
                13 // enter
            ].includes(event.keyCode)) {
                inputDate.input.blur()
            }

            if (inputDate.typeable) {
                const typedDate = !isNaN(moment(inputDate.input.value, dateFormat).toDate()) ? moment(inputDate.input.value, dateFormat).toDate() : new Date(inputDate.input.value)
                if (!isNaN(typedDate)) {
                    inputDate.typedDate = inputDate.input.value
                    inputDate.$emit('typedDate', typedDate)
                }
            }
        }

        inputDate.inputBlurred = function () {

            var date = !isNaN(moment(inputDate.input.value, dateFormat).toDate()) ? moment(inputDate.input.value, dateFormat).toDate() : Date.parse(inputDate.input.value);

            if (inputDate.typeable && isNaN(date)) {
                inputDate.clearDate()
                inputDate.input.value = null
                inputDate.typedDate = null
            }

            inputDate.$emit('closeCalendar')
        }
    },
}

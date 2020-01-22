window.Popper = require('popper.js').default;
window.$ = window.jQuery = require('jquery');
window.Dropzone = require('dropzone');
require('./bootstrap');
window.Vue = require('vue');
Vue.component('ad', require('./components/AdComponent.vue').default);
const app = new Vue({
    el: '#app'
});
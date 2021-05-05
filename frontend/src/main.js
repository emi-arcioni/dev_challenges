import Vue from 'vue'
import App from './App.vue'
import router from "./router";
import 'bootstrap';
import Toasted from 'vue-toasted';
import Vuelidate from 'vuelidate';

Vue.config.productionTip = false;
Vue.use(Toasted);
Vue.use(Vuelidate);

new Vue({
  router,
  render: h => h(App),
}).$mount('#app')

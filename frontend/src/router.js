import Vue from "vue";
import VueRouter from 'vue-router'

import Home from "./components/Home.vue";
import Voting from "./components/Voting.vue";
    
Vue.use(VueRouter)

const routes = [
    {
      path: "/",
      name: "home",
      component: Home
    },
    {
      path: "/voting/:id",
      name: "voting",
      component: Voting
    }
];

const router = new VueRouter({
    mode: "history",
    routes
});
  
export default router;
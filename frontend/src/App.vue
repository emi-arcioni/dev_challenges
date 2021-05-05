<template>
  <div id="app">
    <div class="container pt-3">
      <div class="row text-center mb-5">
        <div class="col-12 mb-3">
          <div id="logo" @click="gotoHome()"></div>
        </div>
        <div class="col-12">
          <h1 class="mb-0">♠ Planning Poker ♠</h1>
        </div>
        <div class="col-12">
          <h2 class="mb-0">Hiring Challenge 👋</h2>
        </div>
      </div>
      <router-view 
        @isLoading="isLoading()"
        @finishedLoading="finishedLoading()"
        @toast="toast($event)"
        ></router-view>
    </div>
    <div class="spinner-container" v-if="showSpinner">
      <div class="spinner-border" role="status">
        <span class="sr-only">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script>
import router from './router';

export default {
  name: 'App',
  data() {
    return {
      showSpinner: false
    }
  },
  methods: {
    isLoading() {
      this.showSpinner = true;
    },
    finishedLoading() {
      this.showSpinner = false;
    },
    toast(message) {
      this.$toasted.show(message, {
        action : {
            text : 'Close',
            onClick : (e, toastObject) => {
                toastObject.goAway(0);
            }
        },
      });
    },
    gotoHome() {
      router.push('/');
    }
  }
}
</script>

<style>
@import'~bootstrap/dist/css/bootstrap.css';

#logo {
  margin: 0 auto;
  height: 35px;
  width: 219px;
  background: url(https://wkncdn.com/newx/assets/build/img/workana-logo-2x.9e13d14c2.png);
  background-repeat: no-repeat;
  background-size: cover;
  cursor: pointer;
}
h1, h2 {
  color: #e76f51;
}
h3 {
  color: #2a9d8f;
}
#voteList {
  margin: 0;
  padding: 0;
  justify-content: center;
  display: flex;
  flex-wrap: wrap;
  list-style: none;
  justify-content: space-around;
}
#voteList li {
  display: flex;
  justify-content: center;
  align-items: center;
  box-sizing: border-box;
  cursor: pointer;
  height: 100px;
  width: 100px;
  margin: 0;
  border-radius: 6px;
  background: #e76f51;
  color: #fff;
  margin: 10px;
  font-size: 22px;
  transition: background-color 0.3s ease font-size 0.3s ease;
}
#voteList li:active {
  background: #2a9d8f;
}
#voteList li.voted {
  cursor: default;
  pointer-events: none;
}
#memberList {
  list-style: none;
}
#memberList li {
  background: #e76f51;
  margin: 0.5em 0;
  padding: 1em;
  border-radius: 5px;
  display: flex;
  align-content: center;
}
#memberList li div {
  width: 50%;
  display: block;
  margin: auto;
}
#memberList li div.name {
  color: #FFF;
}
#memberList li div.vote {
  color: #FFF;
  text-shadow: none;
  font-size: 1.5em;
}
#issue {
  color: #2a9d8f;
  width: 75px;
  text-align: center;
}
.spinner-container {
  display: flex;
  justify-content: center;
  align-items: center;
  position: fixed;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  background-color: rgba(255,255,255,0.8);
}
</style>

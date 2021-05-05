<template>
  <div id="container">
    <div class="row mb-5">
      <div class="col-6 offset-3">
        <div class="vote mb-4">
          <h3 class="text-center">Voting issue #{{ issue_id }}</h3>
          <p class="text-center" v-if="issue">Status: {{ issue.status }}</p>
          <ul id="voteList">
            <li v-for="vote in validVotes"
                :key="vote"
                :class="{voted: issue && issue.status == 'reveal'}"
                @click="emitVote(vote)">{{vote}}</li>
          </ul>
        </div>
        <div class="members">
          <h3>
            Joined users: {{members.length}}
          </h3>
          <ul class="list-group mb-3">
            <li class="list-group-item" :key="member.name" v-for="member in members">
              {{member.name}} {{ member.name == member_name ? '(you)':  '' }}
              status: {{member.status}}
              {{member.status == 'voted' || member.status == 'passed' ? ' ✅' : ''}}
              {{member.value ? ' - ' + member.value : ''}}
            </li>
            <li v-if="issue && issue.status == 'reveal'" class="list-group-item active">
              Average: {{ issue.avg }}
            </li>
          </ul>
          <button 
            class="btn btn-secondary" 
            v-if="issue && issue.status == 'voting'"
            @click="leaveIssue()">Leave issue</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import router from '../router';
import Pusher from 'pusher-js';
import axios from "axios";

export default {
  name: 'Voting',
  data() {
    return {
      pusher: null,
      channel: null,
      issue_id: null,
      issue: null,
      member_name: null,
      validVotes: [1,2,3,5,8,13,20,40,'?'],
      members: [],
      responsesDemo: {
        php: null,
      }
    };
  },
  created() {
    this.issue_id = this.$route.params.id;
    this.member_name = this.$route.params.name;
    this.subscribe();
  },
  async mounted() {
    this.getIssue();
  },
  beforeRouteLeave (to, from, next) {
    this.pusher.unsubscribe('workana-channel');
    next();
  },
  methods: {
    subscribe() {
      this.pusher = new Pusher(process.env.VUE_APP_PUSHER, {
        cluster: 'mt1'
      });

      this.channel = this.pusher.subscribe('workana-channel');
      this.channel.bind('reload-issue', (data) => {
        this.getIssue();
        if (data.message) {
          this.$emit('toast', data.message);
        }
      });
    },
    async emitVote(vote) {
      if (this.issue.status == 'reveal') return;
      this.$emit('isLoading');
      const payload = {
        value: vote,
      };
      await axios.post(process.env.VUE_APP_API_URL + '/issues/' + this.issue_id + '/vote', payload, {
          withCredentials: true
      });
    },
    async getIssue() {
      const response = await axios.get(process.env.VUE_APP_API_URL + '/issues/' + this.issue_id, {
          withCredentials: true
      });
      if (response.status == 404) router.push('/');
      this.$emit('finishedLoading');
      if (this.issue && response.data.status == 'reveal') {
        this.$emit('toast', 'All users voted');
      }
      this.issue = response.data;
      if (response.data.members.length) {
        this.members = response.data.members;
      }
      const m = this.members.filter(member => {
        return member.name == this.member_name
      });
      if (!m.length) router.push('/');
    },
    async leaveIssue() {
      this.$emit('isLoading');
      const payload = {};
      await axios.post(process.env.VUE_APP_API_URL + '/issues/' + this.issue_id + '/leave', payload, {
          withCredentials: true
      });
      router.push('/');
    }
  }
}
</script>


<style scoped>

</style>

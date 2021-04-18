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

    <pre style="text-align: left;">
      <strong>PHP res:</strong>
      {{responsesDemo.php}}
    </pre>

  </div>
</template>

<script>
import router from '../router';
import Pusher from 'pusher-js';

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
      this.channel.bind('reload-issue', () => {
        this.getIssue();
      });
    },
    async emitVote(vote) {
      if (this.issue.status == 'reveal') return;
      const requestOptions = {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ 
            name: this.member_name,
            value: vote
          })
      };
      await fetch(process.env.VUE_APP_API_URL + '/issues/' + this.issue_id + '/vote', requestOptions);
      this.getIssue();
    },
    async getIssue() {
      console.log("getIssue");
      const response = await fetch(process.env.VUE_APP_API_URL + '/issues/' + this.issue_id);
      if (response.status == 404) router.push('/');
      const data = await response.json();
      this.issue = data;
      if (data.members.length) {
        this.members = data.members;
      }
      const m = this.members.filter(member => {
        return member.name == this.member_name
      });
      if (!m.length) router.push('/');
      this.responsesDemo.php = JSON.stringify(data);
    },
    async leaveIssue() {
      const requestOptions = {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ 
            name: this.member_name
          })
      };
      await fetch(process.env.VUE_APP_API_URL + '/issues/' + this.issue_id + '/leave', requestOptions);
      router.push('/');
    }
  }
}
</script>


<style scoped>

</style>

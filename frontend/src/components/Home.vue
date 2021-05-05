<template>
    <div class="row">
        <div class="col-6 offset-3">
            <div class="alert alert-danger mt-4" v-if="errors.length">
                <strong>Please fix the following error(s)</strong>
                <ul class="mb-0">
                    <li v-for="error in errors" :key="error">{{ error }}</li>
                </ul>
            </div>
            <form id="app" @submit="checkForm">
                <div class="form-group">
                    <label for="input1">Your name</label>
                    <input type="text" class="form-control" id="input1" name="name" v-model="name">
                    <small class="form-text text-muted">Please enter your name in order to identify yourself in the planning poker</small>
                </div>
                <div class="form-group">
                    <label for="input2">Select an issue</label>
                    <select name="issue_id" id="input2" class="form-control" v-model="issue_id">
                        <option v-bind:value="null">Select...</option>
                        <option v-bind:value="'new'">New</option>
                        <option v-for="issue in issues" :key="issue.id" v-bind:value="issue.id">{{ issue.id }}</option>
                    </select>
                </div>
                <div class="form-group" v-if="issue_id == 'new'">
                    <input type="number" class="form-control" name="new_issue" v-model="new_issue">
                    <small class="form-text text-muted">Please enter the issue ID</small>
                </div>
                <button type="submit" class="btn btn-primary">{{ issue_id == 'new' ? 'Create & Join' : 'Join' }}</button>
            </form>
        </div>
    </div>
</template>

<script>
import router from '../router';
import Pusher from 'pusher-js';
import axios from "axios";

export default {
    name: 'Home',
    data() {
        return {
            pusher: null,
            channel: null,
            issues: [],
            errors: [],
            name: null,
            issue_id: null,
            new_issue: null
        }
    },
    async mounted() {
        this.pusher = new Pusher(process.env.VUE_APP_PUSHER, {
            cluster: 'mt1'
        });

        this.channel = this.pusher.subscribe('workana-channel');
        this.channel.bind('reload-issue', () => {
            this.getIssues();
        });
        this.getIssues();
    },
    methods: {
        async getIssues() {
            this.$emit('isLoading');
            const response = await axios.get(process.env.VUE_APP_API_URL + '/issues', { withCredentials: true });
            this.issues = response.data;
            this.$emit('finishedLoading');
        },
        async checkForm(e) {
            e.preventDefault();
            this.errors = [];
            if (!this.name) {
                this.errors.push('Your name is mandatory');
            }
            if (!this.issue_id) {
                this.errors.push('The issue is mandatory');
            } else if (this.issue_id == 'new' && !this.new_issue) {
                this.errors.push('The new issue is mandatory');
            }

            if (!this.errors.length) { 
                let issue_id;
                if (this.issue_id == 'new') {
                    issue_id = this.new_issue;
                } else {
                    issue_id = this.issue_id;
                }               
                const payload = {
                    name: this.name,
                };
                try {
                    this.$emit('isLoading');
                    const response = await axios.post(process.env.VUE_APP_API_URL + '/issues/' + issue_id + '/join', payload, { withCredentials: true });
                    if (response.status == 200) {
                        router.push('voting/' + issue_id + '/' + this.name);
                    } else {
                        this.$emit('finishedLoading');
                    }
                } catch (e) {
                    this.$emit('finishedLoading');
                    this.errors.push(e.response.data.error.description);
                }
            }
        }
    }
}
</script>

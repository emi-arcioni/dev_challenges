<template>
    <div class="row">
        <div class="col-6 offset-3">
            <div class="alert alert-danger mt-4" v-if="errors.length">
                <strong>Please fix the following error(s)</strong>
                <ul class="mb-0">
                    <li v-for="error in errors" :key="error">{{ error }}</li>
                </ul>
            </div>
            <form id="app" @submit="checkForm" :class="{ 'was-validated': formSubmitted && $v.$invalid }" novalidate>
                <div class="form-group">
                    <label for="input1">Your name</label>
                    <input type="text" class="form-control" id="input1" name="name" v-model="name" required>
                    <small class="form-text text-muted">Please enter your name in order to identify yourself in the planning poker</small>
                    <div class="invalid-feedback" v-if="!$v.name.required">Name is required</div>

                </div>
                <div class="form-group">
                    <label for="input2">Select an issue</label>
                    <select name="issue_id" id="input2" class="form-control" v-model="issue_id" required>
                        <option v-bind:value="null">Select...</option>
                        <option v-bind:value="'new'">New</option>
                        <option v-for="issue in issues" :key="issue.id" v-bind:value="issue.id">{{ issue.id }}</option>
                    </select>
                    <div class="invalid-feedback" v-if="!$v.issue_id.required">Issue is required</div>
                </div>
                <div class="form-group" v-if="issue_id == 'new'">
                    <input type="number" class="form-control" name="new_issue" v-model="new_issue" required>
                    <small class="form-text text-muted">Please enter the issue ID</small>
                    <div class="invalid-feedback" v-if="!$v.new_issue.required">The issue ID is required</div>
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
import { required, requiredIf } from 'vuelidate/lib/validators';

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
            new_issue: null,
            formSubmitted: false
        }
    },
    validations: {
        name: {
            required
        },
        issue_id: {
            required
        },
        new_issue: {
            required: requiredIf(function (form) {
                return form.issue_id == 'new'
            })
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
            try {
                const response = await axios.get(process.env.VUE_APP_API_URL + '/issues', { withCredentials: true });
                this.issues = response.data;
            } catch (e) {
                this.errors.push(e.response.data.error.description);
            }
            this.$emit('finishedLoading');
        },
        async checkForm(e) {
            e.preventDefault();
            this.formSubmitted = true;

            if (this.$v.$invalid) return;
            this.errors = [];
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
</script>

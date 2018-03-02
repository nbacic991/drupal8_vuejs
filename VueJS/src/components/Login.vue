<template>
  <div>
    <div v-if="isLoggedIn == false">
        <h3>Login</h3>
        <input type="text" v-model="name">
        <input type="password" v-model="pass">
        <br>
        <button @click="LogIn">Login</button>
    </div>
    <div v-if="isLoggedIn == true">
        Hello {{this.name}}
        <br>
        <button @click="LogOut">Logout</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
    data() {
        return {
            name: this.name,
            pass: this.pass,
            urlIn: 'http://drupal8vue.dev.loc/user/login?_format=json',
            urlOut: 'http://drupal8vue.dev.loc/user/logout?_format=json',
            csrfToken: '',
            logoutToken: '',
            isLoggedIn: false,
        }
    },
    http: {
        headers: {
            'Accept' : 'json',
            'Content-Type': 'application/json+hal_json',
            'X-CSRF-TOKEN': 'JyGYuXPQiagnYKZkl_JAPGdRoj3tZiDulxTAH6xETjU'
        }
    },
    methods: {
        LogIn(){
            let data = JSON.stringify({
                name: this.name,
                pass: this.pass
            });
            this.$http.post(this.urlIn , data, {
                credentials: "same-origin",
                    headers: {
                    'Content-Type': 'application/json',
                    },
                }
            )
            .then(response => {
                this.response = response.data;
                this.csrfToken = response.data.csrf_token;
                this.logoutToken = response.data.logout_token;
                console.log(this.logoutToken)
                this.isLoggedIn = true;
            });

            console.log('Logged In !');
        },
        LogOut() {
            this.$http.get('http://drupal8vue.dev.loc/user/logout', {
                credentials: "same-origin",
                    headers: {
                    'Content-Type': 'application/json',
                    },
                }
            );
            this.name = ''
            this.pass = ''
            this.isLoggedIn = false
            console.log('Logged Out !');
        }
    }
}
</script>

<style>

</style>

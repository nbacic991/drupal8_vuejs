<template>
  <div>
    <div v-if="isLoggedIn = !isLoggedIn">
        <input type="text" v-model="name">
        <input type="password" v-model="pass">
        <button @click="LogIn">Login</button>
    </div>
    <div v-if="isLoggedIn = !isLoggedIn">
        Hello {{name}}
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
            urlOut: 'https://drupal8vue.dev.loc/user/logout?_format=json',
            csrfToken: '0AiRM0_Huv4GLsdvbAVDejXcaazrYWxq_5uMmmnyKlg',
            logout_token: 'Znn8h_T6J9K8NEamb_jjUPdrRN6WO6HhPK5ltMpIJTk',
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
    mounted(){

    },
    methods: {
        LogIn(){
            console.log('Logged In');
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
            console.log(response.data.logout_token)
            this.isLoggedIn = true;
        });
      },
    }
}
</script>

<style>

</style>

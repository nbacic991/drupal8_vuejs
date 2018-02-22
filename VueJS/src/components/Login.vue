<template>
  <div>
      <input type="text" v-model="name">
      <input type="password" v-model="pass">
      <button @click="LogIn">Login</button>
  </div>
</template>

<script>
import axios from 'axios'

export default {
    data() {
        return {
            name: this.name,
            pass: this.pass,
            formId: "user_login_form",
            url: 'http://drupal8vue.dev.loc/user/login?_format=json',
            csrfToken: '0AiRM0_Huv4GLsdvbAVDejXcaazrYWxq_5uMmmnyKlg',
            logout_token: 'Znn8h_T6J9K8NEamb_jjUPdrRN6WO6HhPK5ltMpIJTk',
        }
    },
    mounted(){

    },
    methods: {
         LogIn(){
            console.log('login clicked');
            
            let data = JSON.stringify({
                name: this.name,
                pass: this.pass,
                form_id: this.formId
                
            });
            axios.post(this.url , data, {
            credentials: "same-origin",
                headers: {
                'Content-Type': 'application/json+hal_json',
                'x-csrf-token': '0AiRM0_Huv4GLsdvbAVDejXcaazrYWxq_5uMmmnyKlg'
                },
            }
            )
            .then(response => {
                console.log('Logged in')
                this.response = response.json;
                this.csrfToken = response.data.csrf_token;
                this.logoutToken = response.data.logout_token;
          });
      }
    }
}
</script>

<style>

</style>

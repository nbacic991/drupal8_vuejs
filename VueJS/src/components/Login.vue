<template>
  <div>
      <h1>Login form</h1>
      <form action="">
        <input type="text" v-model="username">
        <input type="password" v-model="password">
        <button @click="logIn">Login</button>
      </form>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
        username: this.username,
        password: this.password,
    }
  },
  methods: {
      logIn(){
          console.log('Clicked !')
          let url = 'http://drupal8vue.dev.loc/user/login';
          let data = JSON.stringify({
              username: this.username,
              password: this.password
          });
          axios.post(url, data, {
              credentials: "same-origin",
              headers: {
                  'Content-Type': 'application/json'
              }
          })
          .then((response) => {
              console.log('Logged in !');
            //   this.response = response.data;
            //   this.csrfToken = response.data.csrf_token;
            //   this.logoutToken = response.data.logout_token;
          })
          .catch(error => {
				console.log('-- ERROR --')
				console.log(error.response)
			});
      }
  }
}
</script>

<style>

</style>
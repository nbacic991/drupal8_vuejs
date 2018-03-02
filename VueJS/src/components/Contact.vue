<template>
  <div>
      <h1>{{formFields.label}}</h1>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data(){
    return {
      formFields: '',
      url: 'http://drupal8vue.dev.loc/contact/contact_us?_format=json',
    }
  },
  http: {
    headers: {
      'Content-Type': 'application/json'
    }
  },
  mounted(){
    axios.get(this.url)
          .then(response => {
            this.formFields = response.data
          })
          .catch( error => {
            console.log('-----error-------');
            console.log(error)
        })
  }
}
</script>

<style>

</style>

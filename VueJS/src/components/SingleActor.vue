<template>
<div >
    <div class="actor" v-if="actor">
        <h1 class="actor__title">{{ actor[0].title }}</h1>
        <img v-bind:src="'http://drupal8vue.dev.loc' + actor[0].field_actor_image" />
        <p class="actor__body"><strong>Full biography : </strong>{{ actor[0].body }}</p>
        <p><strong>Movies :</strong> {{actor[0].field_movies}}</p>
        <br>
        <router-link to="/actors">Go back</router-link>
    </div>
</div>
</template>

<script>
  import axios from 'axios';

  export default {
    props: ['id'],

    data() {
      return {
        actor: null,
        endpoint: 'http://drupal8vue.dev.loc/api/actors/',
      }
    },

    methods: {
      getActor(id) {
        axios(this.endpoint + id)
          .then(response => {
            this.actor = response.data
          })
          .catch( error => {
            console.log('-----error-------');
            console.log(error)
        })
      }
    },
    
    created() {
      this.getActor(this.id);
    },

    watch: {
      '$route'() {
        this.getActor(this.id);
      }
    }
  }
</script>
<style lang="scss" scoped>

</style>

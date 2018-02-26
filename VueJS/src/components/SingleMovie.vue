
<template>
<div >
    <div class="movie" v-if="movie" >
        <div class="left">
          <h1 class="movie__title">{{ movie[0].title[0].value }}</h1>
          <img v-bind:src="movie[0].field_movie_poster[0].value" />
          <p class="movie__body" v-html="movie[0].body[0].value"><strong>Description : </strong></p>
          <strong>Actors :</strong>
          <span v-for="(actor, key) in movie[0].field_actors" :key="key">
            {{actor.value}},
          </span>
          <p><strong>Genre: </strong>{{movie[0].field_genre[0].value}}</p>
        </div>
        <div class="right">
          <strong>Official trailer: </strong>
          <youtube v-bind:video-id="movie[0].field_trailer[0].uri | subStr"></youtube>
        </div>
        <br>
        <router-link to="/movies">
          <button class="btn">Go back</button>
        </router-link>
    </div>
</div>
</template>

<script>
  import axios from 'axios';
  import VueYouTubeEmbed from 'vue-youtube-embed'

  export default {
    props: ['id'],

    data() {
      return {
        movie: null,
        endpoint: 'http://drupal8vue.dev.loc/api/movies-test/',
      }
    },
    methods: {
      getMovie(id) {
        axios.get(this.endpoint + id)
          .then(response => {
            this.movie = response.data
          })
          .catch( error => {
            console.log('-----error-------');
            console.log(error)
        })
      },
    },
    
    created() {
      this.getMovie(this.id);
    },

    watch: {
      '$route'() {
        this.getMovie(this.id);
      }
    },
    filters: {
      subStr: function(string) {
        return string.substring(32);
      }
    }
  }
</script>

<style lang="scss" scoped>

</style>

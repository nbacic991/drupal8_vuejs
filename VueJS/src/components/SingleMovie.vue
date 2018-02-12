
<template>
<div >
    <div class="movie" v-if="movie">
        <div class="left">
          <h1 class="movie__title">{{ movie[0].title }}</h1>
          <img v-bind:src="'http://drupal8vue.dev.loc' + movie[0].field_movie_poster" />
          <p class="movie__body"><strong>Description : </strong>{{ movie[0].body }}</p>
          <p><strong>Actors :</strong> {{movie[0].field_actors}}</p>
          <p><strong>Genre: </strong>{{movie[0].field_movie_genre}}</p>
        </div>
        <div class="right">
          <strong>Official trailer: </strong>
          <youtube v-bind:video-id="movie[0].field_trailer | subStr"></youtube>
        </div>
        <br>
        <router-link to="/movies">Go back</router-link>
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
        endpoint: 'http://drupal8vue.dev.loc/api/movies/',
      }
    },
    methods: {
      getMovie(id) {
        axios(this.endpoint + id)
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

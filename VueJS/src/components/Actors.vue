<template>
<div class="container">
    <div class="control">
        <input class="input" type="text" v-model="search" placeholder="Search by title">
    </div>
    <div class="actors">
        <div v-for="actor in filteredActors" :key="actor.nid" class="single-actor">
            <router-link :to="{ name: 'actor', params: { id: actor.nid } }" :key="actor.nid">
            <img v-bind:src="'http://drupal8vue.dev.loc' + actor.field_actor_image" />
            </router-link>
            <p><strong>Name:</strong>{{actor.title}}</p>
            <p><strong>Short bio: </strong></p>
            <p>{{actor.body}}</p>
            <router-link
                class="link"
                :to="{ name: 'actor', params: { id: actor.nid } }" :key="actor.title">
                Read full bio...
            </router-link>
            <br>
        </div>
        <div id="actors-list-bottm"></div>
    </div>
    
</div>
</template>

<script>
import axios from 'axios'

/**
 * Declaring variables
 */

export default {
    data () {
      return {
        actors: [],
        endpoint: 'http://drupal8vue.dev.loc/api/actors/',
        search: '',
      }
    },

    created() {
      this.getAllActors();
      
    },
    computed: {
        filteredActors: function() {
            return this.actors.filter((actor) => {
                return actor.title.match(this.search);
            });
        },
    },
    methods: {
        getAllActors() {
        axios.get(this.endpoint)
          .then(response => {
            this.actors = response.data;
          })
          .catch(error => {
            console.log('-----error-------');
            console.log(error);
          })
        },
    }
  }
</script>

<style lang="scss" scoped>
.control {
    margin-bottom: 10px;
}
.actors {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    .single-actor {
        flex-basis: 10%;
        padding: 10px;
        border: 1px solid gray;
    }
    .actors-list-bottm {
        margin-top: 50px;
        height: 50px;
        background: blue;
        width: 100%;
    }
}
</style>

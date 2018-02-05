<template>
<div>
    <h1>This is my actors page</h1>
    <input type="text" v-model="search" placeholder="Search by title">
    <div class="actors">
        <div v-for="actor in filteredActors" :key="actor.title" class="single-actor">
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
        
    </div>
</div>
</template>

<script>
import axios from 'axios'

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
        }
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
      }
    }
  }
</script>

<style lang="scss" scoped>
.actors {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    grid-column-gap: 20px;
}
.single-actor {
    margin: 30px;
}
input {
	border: solid 1px #e8e8e8;
	font-family: 'Roboto', sans-serif;
	padding: 10px 7px;
	margin-bottom: 15px;
	outline: none;
}
.link {
    text-decoration: none;
    font-weight: normal;
}
</style>

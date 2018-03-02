<template>
  <nav>
      <ul>
          <li v-for="menuItem in menuItems" :key="menuItem.id" v-bind:class="menuItem.title.toLowerCase()">
            <router-link v-bind:to="menuItem.link__uri">{{ menuItem.title}}</router-link>
          </li>
      </ul>
  </nav>
</template>

<script>
const url = "http://drupal8vue.dev.loc/api/menu-items";

export default {
    
    data: function() {
      return {
          menuItems: [],
      }
    },
    mounted() {
      fetch(url)
        .then(response => response.json())
        .then((data) => {
          this.menuItems = data;
        })
    },

}
</script>


<style lang="scss" scoped>
li {
    display: inline-block;
    text-decoration: none;
    padding: 10px;
    border: 1px solid #d3d3d3;
    margin: 5px;
    a {
        text-decoration: none;
        font-weight: 800;
        color: #000;
    }
}
</style>

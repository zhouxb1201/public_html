<template>
  <div class="banner" v-if="src" @click="toLink(link)">
    <img :src="src" :onerror="$ERRORPIC.noRectangle">
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    src: {
      type: String
    },
    link: {
      type: String
    }
  },
  methods: {
    toLink(link) {
      if (link) {
        this.$router.push(link);
      }
    }
  }
};
</script>

<style scoped>
.banner {
  width: 100%;
  overflow: hidden;
  min-height: 56px;
}

.banner img {
  max-width: 100%;
  width: 100%;
  height: auto;
  display: block;
}
</style>

<template>
  <div :class="item.id">
    <van-swipe :autoplay="3000">
      <van-swipe-item v-for="(child,index) in item.data" :key="index">
        <img
          v-if="!index"
          v-lazy="child.imgurl"
          :key="child.imgurl"
          pic-type="rectangle"
          @click="toLink(child.linkurl)"
        />
        <img v-else :src="child.imgurl | BASESRC" @click="toLink(child.linkurl)" />
      </van-swipe-item>
    </van-swipe>
  </div>
</template>

<script>
import { Swipe, SwipeItem } from "vant";
export default {
  name: "tpl_banner",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  methods: {
    toLink(params) {
      this.$router.push(params);
    }
  },
  components: {
    [Swipe.name]: Swipe,
    [SwipeItem.name]: SwipeItem
  }
};
</script>

<style scoped>
.banner img {
  width: 100%;
  display: block;
}
</style>

<template>
  <div :class="item.id" style="position: relative;">
    <van-swipe class="swipe-box" :autoplay="5000" :style="swipeStyle" @change="change">
      <van-swipe-item
        class="item"
        v-for="(child,index) in item.data"
        :key="index"
        @click="click(index)"
      >
        <img v-if="!index" v-lazy="child" :key="child" pic-type="goods" />
        <img v-else :src="child" :onerror="$ERRORPIC.noGoods" />
      </van-swipe-item>
      <div class="swipe-indicator" :class="[item.style.position,item.style.shape]" slot="indicator">
        <span
          v-for="s in length"
          :key="s"
          class="span"
          :class="s==active?'active':''"
          :style="s==active?{backgroundColor:item.style.color}:''"
        ></span>
      </div>
    </van-swipe>
    <PlayVideoBtn v-if="video" :src="video | BASESRC" />
  </div>
</template>

<script>
import { Swipe, SwipeItem, ImagePreview } from "vant";
import PlayVideoBtn from "../goods/component/detail/PlayVideoBtn";
export default {
  name: "tpl_detail_banner",
  data() {
    return {
      active: 1,
      length: this.item.data.length || 0,
      swipeStyle: {
        maxHeight: document.body.offsetWidth + "px",
        minHeight: document.body.offsetWidth / 2 + "px"
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    video() {
      return this.item.params.video || "";
    }
  },
  methods: {
    change(index) {
      this.active = index + 1;
    },
    click(index) {
      ImagePreview({
        images: this.item.data,
        startPosition: index
      });
    }
  },
  components: {
    [Swipe.name]: Swipe,
    [SwipeItem.name]: SwipeItem,
    PlayVideoBtn
  }
};
</script>

<style scoped>
.swipe-box {
  background: #f5f9ff;
}

.item {
  overflow: hidden;
}

.item img {
  width: 100%;
  height: auto;
  display: block;
}

.swipe-indicator {
  display: flex;
  position: absolute;
  width: 100%;
  bottom: 10px;
  padding: 0 15px;
  justify-content: center;
}

.swipe-indicator.center {
  justify-content: center;
}

.swipe-indicator.left {
  justify-content: flex-start;
}

.swipe-indicator.right {
  justify-content: flex-end;
}

.swipe-indicator .span {
  opacity: 0.3;
  width: 6px;
  height: 6px;
  background-color: #ebedf0;
  -webkit-transition: opacity 0.2s;
  transition: opacity 0.2s;
  margin: 0 4px;
}

.swipe-indicator .span.active {
  opacity: 1;
  background-color: #1989fa;
}

.swipe-indicator.round .span {
  border-radius: 100%;
}

.swipe-indicator.square .span {
  border-radius: 0px;
}

.swipe-indicator.rectangle .span {
  width: 10px;
}
</style>

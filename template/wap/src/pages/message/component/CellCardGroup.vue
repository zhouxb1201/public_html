<template>
  <van-cell value-class="panel-item" clickable @click="toPath">
    <div class="item-left">
      <img class="img" :src="img" :onerror="$ERRORPIC.noGoods" />
    </div>
    <div class="item-right">
      <div class="title">
        <div class="left-text">{{title}}</div>
      </div>
      <div class="text">
        <div class="left-text text-secondary">{{content}}</div>
      </div>
      <div class="text" v-if="type > 0">
        <div class="left-text text-secondary">{{ type > 2 ? '收藏了你的干货' : '赞了你的干货'}}</div>
      </div>
      <div class="text">
        <div class="left-text text-secondary">{{time | formatDate}}</div>
      </div>
    </div>
    <div class="item-edge">
      <img :src="thing_type == 1 ? cover : video_img" :onerror="$ERRORPIC.noGoods" />
    </div>
  </van-cell>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    id:[Number,String],
    img: [String],
    title: [String],
    content: [String],
    time: [String, Number],
    cover: [String],
    type: [String, Number],
    thing_type: [Number, String],
    video_img:[String]
  },
  methods: {
    toPath() {
      const $this = this;
      if ($this.thing_type == 1) {
        this.$router.push({
          name: "thingcircle-grapdetail",
          params: {
            thingid: $this.id
          }
        });
      } else if ($this.thing_type == 2) {
        this.$router.push({
          name: "thingcircle-vediodetail",
          params: {
            thingid: $this.id
          }
        });
      }
    }
  }
};
</script>
<style scoped>
.panel-item {
  display: flex;
}

.item-left {
  flex: 0.2;
  margin-right: 10px;
  position: relative;
}

.item-left.dot::after {
  content: "";
  display: block;
  position: absolute;
  right: 0;
  top: 0px;
  width: 8px;
  height: 8px;
  z-index: 10;
  background: red;
  border-radius: 100%;
}

.img {
  width: 48px;
  height: 48px;
  display: block;
  border-radius: 8px;
  background: #f8f8f8;
}

.item-right {
  flex: 1.8;
  flex-direction: column;
  overflow: hidden;
}

.title {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left-text {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.right-text {
  font-size: 12px;
}

.text {
  display: flex;
  align-items: center;
}
.item-edge {
  width: 50px;
  position: relative;
}
.item-edge img {
  display: block;
  width: 50px;
  height: 70px;
}
</style>
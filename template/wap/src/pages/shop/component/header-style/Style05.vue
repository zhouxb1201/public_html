<template>
  <div class="box">
    <div class="logo">
      <div class="img">
        <img v-lazy="info.shop_logo" :key="info.shop_logo" pic-type="square">
      </div>
    </div>
    <div class="info">
      <div>
        <div class="name">{{info.shop_name}}</div>
        <van-button class="btn" size="mini" :loading="isLoading" @click="onCollect">
          <van-icon :name="btnIconName" class="btn-icon"/>收藏
        </van-button>
      </div>
      <div>
        <div class="fans">
          粉丝
          <span>{{info.fans_number}}</span>
        </div>
        <div class="score">
          综合评分
          <Star
            class="star"
            :value="info.evaluate.comprehensive"
            :size="10"
            color="#ff454e"
            voidColor="#ff454e"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Star from "@/components/Star";
import collection from "./collection";
export default {
  data() {
    return {};
  },
  mixins: [collection],
  props: {
    info: Object
  },
  components: {
    Star
  }
};
</script>

<style scoped>
.box {
  display: flex;
  align-items: center;
}

.box .logo {
  width: 30%;
  padding: 0 10px 0 15px;
}

.box .logo .img {
  border-radius: 4px;
  background: #ffffff;
  padding-bottom: 56%;
  width: 100%;
  position: relative;
  overflow: hidden;
  margin-right: 10px;
}

.box .logo .img img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.box .info {
  height: 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.box .info .name {
  max-width: 120px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.box .info .btn {
  margin-left: 10px;
  color: #ff454e;
  border: transparent;
  background: transparent;
}

.box .info .btn .btn-icon {
  vertical-align: middle;
  margin-right: 4px;
}

.box .info .btn .btn-icon::before {
  color: inherit;
}

.box .info > div {
  display: flex;
  line-height: 1.8;
  align-items: center;
}

.box .info .fans {
  padding-right: 10px;
  font-size: 10px;
}

.box .info .score {
  font-size: 10px;
  display: flex;
}

.box .info .score .star {
  margin-left: 4px;
}
</style>

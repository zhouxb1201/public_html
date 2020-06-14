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
        <van-button class="btn" round size="mini" :loading="isLoading" @click="onCollect">
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
            color="#043f8d"
            voidColor="#043f8d"
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
  flex-direction: column;
  justify-content: center;
}

.box .logo {
  width: 13.334%;
}

.box .logo .img {
  border-radius: 50%;
  position: relative;
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #ffffff;
}

.box .logo .img img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.box .info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #043f8d;
}

.box .info .name {
  max-width: 120px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.box .info .btn {
  margin-left: 10px;
  color: #043f8d;
  border: transparent;
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
  line-height: 1.6;
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

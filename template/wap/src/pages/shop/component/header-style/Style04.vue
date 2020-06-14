<template>
  <div class="box">
    <div class="logo">
      <div class="img">
        <img v-lazy="info.shop_logo" :key="info.shop_logo" pic-type="square">
      </div>
    </div>
    <div class="info">
      <div class="name">{{info.shop_name}}</div>
    </div>
    <div class="foot">
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
          color="#fff"
          voidColor="#fff"
        />
      </div>
      <div class="collec">
        <van-button class="btn" round size="mini" :loading="isLoading" @click="onCollect">
          <van-icon :name="btnIconName" class="btn-icon"/>收藏
        </van-button>
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
  padding-bottom: 18px;
}

.box .info .name {
  max-width: 120px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.box .info > div {
  display: flex;
  line-height: 1.6;
  align-items: center;
}

.foot {
  display: flex;
  align-items: center;
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 22px;
  background: rgba(0, 0, 0, 0.2);
  font-size: 10px;
  color: #fff;
}

.foot > div {
  flex: 1;
  text-align: center;
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}

.score .star {
  margin-left: 4px;
}

.btn {
  color: #fff;
  border: transparent;
  background: transparent;
}

.btn .btn-icon {
  vertical-align: middle;
  margin-right: 4px;
}

.btn .btn-icon::before {
  color: inherit;
}
</style>

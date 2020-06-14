<template>
  <div class="box">
    <div class="logo">
      <div class="img">
        <img :src="info.member_img | BASESRC" :onerror="$ERRORPIC.noAvatar" />
      </div>
    </div>
    <div class="info">
      <div>
        <div class="name">{{info.name}}</div>
        <van-button size="mini" round plain class="tag" to="/member/level">{{info.level_name}}</van-button>
      </div>
      <div v-if="$store.getters.extend_code">
        <van-tag round color="#a5130b" class="sup">
          推荐码：
          <span class="text">{{$store.getters.extend_code}}</span>
          <van-icon
            name="v-icon-copy"
            class="a-copy"
            :data-clipboard-text="$store.getters.extend_code"
            @click="onCopy"
          />
        </van-tag>
      </div>
    </div>
  </div>
</template>

<script>
import { clipboard } from "@/mixins";
export default {
  data() {
    return {};
  },
  mixins: [clipboard],
  props: {
    info: Object
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

.logo {
  width: 13.334%;
}

.logo .img {
  border-radius: 50%;
  position: relative;
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #ffffff;
}

.logo .img img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.info {
  color: #fff3d1;
  margin-bottom: 10px;
}

.info > div {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 12px;
  line-height: 1;
  margin: 5px 0;
  height: 22px;
}

.info .name {
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #ffe71e;
  font-size: 14px;
}

.info .btn-icon {
  max-width: 12px;
}

.info .tag {
  color: #f8856a;
  margin-left: 8px;
  padding: 2px 4px;
  line-height: 1;
  height: 16px;
  display: block;
  border: 0;
}

.info .sup .text {
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 90px;
  vertical-align: middle;
}
</style>

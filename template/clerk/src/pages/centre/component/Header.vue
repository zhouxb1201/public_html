<template>
  <div class="head">
    <div class="info">
      <div class="img">
        <img :src="$store.getters.avatar | BASESRC" :onerror="$ERRORPIC.noAvatar" />
      </div>
      <div class="text">
        <div class="name">
          <span>{{info.assistant_name}}</span>
          <span class="fff">{{info.jobs_info.jobs_name}}</span>
        </div>
        <div class="store fff">
          <span>{{info.shop_name}}</span>
          <span>（{{info.store_info.store_name}}）</span>
          <router-link class="a-link e-handle" to="/store">切换门店</router-link>
        </div>
      </div>
    </div>
    <router-link class="set e-handle" to="/account/set">
      <van-icon name="setting-o" />
    </router-link>
  </div>
</template>

<script>
export default {
  computed: {
    info() {
      return this.$store.state.account.info;
    }
  }
};
</script>

<style scoped>
.head {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 130px;
  display: flex;
  background: #d03840;
}

.head .info {
  display: flex;
  align-items: center;
  z-index: 1;
  margin-left: 30px;
}

.head .info .img {
  width: 60px;
  height: 60px;
  overflow: hidden;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.head .info .img img {
  width: 100%;
  height: 100%;
}

.head .info .text {
  margin-left: 10px;
  color: #fff3d1;
  line-height: 1.6;
}

.head .set {
  position: absolute;
  right: 20px;
  top: 20px;
  z-index: 1;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 20px;
  border-radius: 50%;
  overflow: hidden;
}
.fff {
  color: #fff;
}
</style>

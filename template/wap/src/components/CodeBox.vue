<template>
  <van-cell-group>
    <van-cell>
      <van-row type="flex" justify="center">
        <van-col span="12" class="box">
          <div class="img">
            <img :src="qrcode">
          </div>
          <div class="code">
            <span>{{code}}</span>
          </div>
        </van-col>
      </van-row>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    qrcode: String,
    code: String
  }
};
</script>

<style scoped>
.box {
  display: flex;
  flex-direction: column;
}

.box .img {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f8f8f8;
}

.box img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.box .code {
  background: #f8f8f8;
  text-align: center;
  padding: 4px 8px;
  margin-top: 10px;
}
</style>

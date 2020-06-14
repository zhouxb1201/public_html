<template>
  <Divider title="砍价帮">
    <van-cell-group :border="false" slot="html">
      <van-cell v-for="(item,index) in list" :key="index">
        <div class="item">
          <div class="img">
            <img :src="item.help_user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar">
          </div>
          <div class="info">
            <div class="name">{{item.help_name}}</div>
            <div class="text">
              帮忙砍掉了
              <span>{{item.help_price}}</span>元
            </div>
          </div>
        </div>
      </van-cell>
    </van-cell-group>
  </Divider>
</template>

<script>
import Divider from "@/components/Divider";
export default {
  props: {
    list: Array
  },
  components: {
    Divider
  }
};
</script>
<style scoped>
.item {
  display: flex;
  align-items: center;
}

.item .img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid #ddd;
}

.item .img img {
  display: block;
  width: 100%;
  height: 100%;
}

.item .info {
  margin-left: 10px;
}

.item .info .name {
  color: #ff9600;
}

.item .info .text {
  color: #666;
}
</style>

<template>
  <Divider title="店主权益">
    <van-cell-group slot="html" :border="false">
      <van-cell value-class="cols" class="cell-cols">
        <div class="item" v-for="(item,index) in cols" :key="index">
          <div class="img">
            <van-icon :name="item.icon" size="26px"/>
          </div>
          <span class="text">{{item.text}}</span>
        </div>
      </van-cell>
      <van-cell value-class="fs-12 text-maintone">
        <div>购买以上任意礼包即可成为店主。成为店主后能享受权益如下：</div>
        <div>1、拥有自己的专属微店。</div>
        <div>2、店主在平台消费即可获得相对应的返利。</div>
        <div>3、分享自己微店给朋友，销售后获得返利。</div>
        <div>4、下级{{$store.state.member.commissionSetText.distributor_name}}成为店主获得返利。</div>
        <div>5、下级销售产品后，亦可获得返利最大三级。</div>
      </van-cell>
    </van-cell-group>
  </Divider>
</template>

<script>
import Divider from "@/components/Divider";
export default {
  data() {
    return {
      cols: [
        {
          icon: "shop-o",
          text: "专属微店"
        },
        {
          icon: "cash-back-record",
          text: "自购返利"
        },
        {
          icon: "after-sale",
          text: "销售返利"
        },
        {
          icon: "replay",
          text: "开店返利"
        }
      ]
    };
  },
  components: {
    Divider
  }
};
</script>

<style scoped>
.cell-cols {
  padding: 0 0 10px;
}

.cols {
  display: flex;
  justify-content: space-around;
}

.item {
  text-align: center;
}

.item .img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid #ddd;
  margin: 6px auto;
  color: #606266;
}

.item .text {
  font-size: 12px;
  color: #606266;
}
</style>

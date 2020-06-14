<template>
  <Layout ref="load" class="giftvoucher-detail bg-f8">
    <Navbar />
    <InfoItem :class="detail.state == 1 ? 'usable' : 'unusable'" :items="detail" />
    <CodeBox :qrcode="detail.gift_voucher_codeImg" :code="detail.gift_voucher_code" />
    <van-cell>
      <div class="cell">
        <div class="text-maintone">使用时间</div>
        <div>{{detail.start_time | formatDate}} ~ {{detail.end_time | formatDate}}</div>
      </div>
      <div class="cell">
        <div class="text-maintone">使用说明</div>
        <div>{{detail.desc}}</div>
      </div>
    </van-cell>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InfoItem from "./component/InfoItem";
import CodeBox from "@/components/CodeBox";
import { GET_GIFTVOUCHERDETAIL } from "@/api/giftvoucher";
export default sfc({
  name: "giftvoucher-detail",
  data() {
    return {
      detail: {}
    };
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_GIFTVOUCHERDETAIL($this.$route.params.recordid)
        .then(({ data }) => {
          $this.detail = data;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  },
  components: {
    InfoItem,
    CodeBox
  }
});
</script>

<style scoped>
.giftvoucher-detail >>> .item .info {
  color: #fff;
}

.giftvoucher-detail >>> .item .info .text .time {
  color: #fff;
}

.giftvoucher-detail >>> .item {
  margin: 0;
  border-radius: 0;
}

.giftvoucher-detail >>> .item::after,
.giftvoucher-detail >>> .item::before {
  background: #fff;
}

.giftvoucher-detail >>> .item.usable {
  background: #ff454e;
}

.giftvoucher-detail >>> .item.unusable {
  background: #909399;
}

.cell {
  margin-bottom: 10px;
}
</style>


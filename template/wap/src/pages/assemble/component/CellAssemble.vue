<template>
  <van-cell-group >
    <van-cell
      icon="v-icon-team"
      is-link
      value="拼团详情"
      :to="'/assemble/detail/'+record_id"
      :replace="replace"
    >
      <div slot="title">
        拼团情况
        <span class="text-maintone">{{now_num}}/{{group_num}}</span>
      </div>
    </van-cell>
    <van-cell>
      <AvatarGroup :list="buyer_list" :group_num="group_num"/>
    </van-cell>
  </van-cell-group>
</template>
<script>
import AvatarGroup from "./AvatarGroup";
export default {
  data() {
    return {
      now_num: 0,
      group_num: 0,
      buyer_list: []
    };
  },
  props: {
    record_id: {
      type: [String, Number],
      default: 0,
      required: true
    },
    replace: {
      type: Boolean,
      default: false
    }
  },
  mounted() {
    const $this = this;
    $this.$store.dispatch("getAssembleDetail", this.record_id).then(detail => {
      $this.now_num = detail.now_num;
      $this.group_num = detail.group_num;
      $this.buyer_list = detail.buyer_list;
    });
  },
  components: {
    AvatarGroup
  }
};
</script>
<style scoped>
.img-box {
  display: flex;
  justify-content: space-around;
  padding: 20px 10px;
}

.img-box .img {
  position: relative;
  width: 50px;
  height: 50px;
}

.img-box .img img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: block;
}

.img-box .img span.van-tag {
  position: absolute;
  right: 0;
  top: 0;
}
</style>

